<?php

namespace App\Http\Controllers;

use App\Models\NotificacionInterna;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth');
        $this->notificationService = $notificationService;
    }

    /**
     * Get notifications for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = NotificacionInterna::where(function($q) use ($user) {
            $q->where('destinatario_email', $user->email)
              ->orWhere('destinatario_user_id', $user->id)
              ->orWhere('tipo', 'sistema'); // System notifications for all users
        });

        // Apply filters
        if ($request->filled('unread_only') && $request->unread_only) {
            $query->whereNull('leida_en');
        }

        if ($request->filled('type')) {
            $query->where('tipo', $request->type);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        // Get unread count
        $unreadCount = NotificacionInterna::where(function($q) use ($user) {
            $q->where('destinatario_email', $user->email)
              ->orWhere('destinatario_user_id', $user->id)
              ->orWhere('tipo', 'sistema');
        })->whereNull('leida_en')->count();

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
                'unread_count' => $unreadCount
            ]
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $notificationId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $notification = NotificacionInterna::where('id', $notificationId)
                ->where(function($q) use ($user) {
                    $q->where('destinatario_email', $user->email)
                      ->orWhere('destinatario_user_id', $user->id);
                })
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notificación no encontrada'
                ], 404);
            }

            if (!$notification->leida_en) {
                $notification->update([
                    'leida_en' => now(),
                    'leida_por_user_id' => $user->id
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída',
                'data' => $notification->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar notificación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $updated = NotificacionInterna::where(function($q) use ($user) {
                $q->where('destinatario_email', $user->email)
                  ->orWhere('destinatario_user_id', $user->id);
            })
            ->whereNull('leida_en')
            ->update([
                'leida_en' => now(),
                'leida_por_user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => "Se marcaron {$updated} notificaciones como leídas"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar notificaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send notification (admin only)
     */
    public function send(Request $request): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'administrador') {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para enviar notificaciones'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'integer|exists:users,id',
            'type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'send_email' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $recipients = User::whereIn('id', $request->recipients)->get();
            $sentCount = 0;
            $errors = [];

            foreach ($recipients as $recipient) {
                try {
                    $notificationData = [
                        'tipo' => $request->type,
                        'titulo' => $request->title,
                        'mensaje' => $request->message,
                        'destinatario_user_id' => $recipient->id,
                        'destinatario_email' => $recipient->email,
                        'enviado_por_user_id' => Auth::id(),
                        'datos_adicionales' => $request->additional_data ?? null
                    ];

                    $notification = $this->notificationService->createInternalNotification($notificationData);

                    if ($notification && $request->get('send_email', false)) {
                        $this->notificationService->sendEmailNotification($notification);
                    }

                    $sentCount++;

                } catch (\Exception $e) {
                    $errors[] = "Error enviando a {$recipient->name}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Notificación enviada a {$sentCount} usuarios",
                'data' => [
                    'sent_count' => $sentCount,
                    'total_recipients' => count($request->recipients),
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar notificaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $stats = [
                'total_notifications' => NotificacionInterna::where(function($q) use ($user) {
                    $q->where('destinatario_email', $user->email)
                      ->orWhere('destinatario_user_id', $user->id);
                })->count(),
                
                'unread_notifications' => NotificacionInterna::where(function($q) use ($user) {
                    $q->where('destinatario_email', $user->email)
                      ->orWhere('destinatario_user_id', $user->id);
                })->whereNull('leida_en')->count(),
                
                'notifications_today' => NotificacionInterna::where(function($q) use ($user) {
                    $q->where('destinatario_email', $user->email)
                      ->orWhere('destinatario_user_id', $user->id);
                })->whereDate('created_at', today())->count(),
                
                'by_type' => NotificacionInterna::where(function($q) use ($user) {
                    $q->where('destinatario_email', $user->email)
                      ->orWhere('destinatario_user_id', $user->id);
                })
                ->select('tipo')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN leida_en IS NULL THEN 1 ELSE 0 END) as unread')
                ->groupBy('tipo')
                ->get(),
                
                'recent_activity' => NotificacionInterna::where(function($q) use ($user) {
                    $q->where('destinatario_email', $user->email)
                      ->orWhere('destinatario_user_id', $user->id);
                })
                ->whereDate('created_at', '>=', now()->subDays(7))
                ->selectRaw('DATE(created_at) as date')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete notification
     */
    public function destroy(Request $request, $notificationId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $notification = NotificacionInterna::where('id', $notificationId)
                ->where(function($q) use ($user) {
                    $q->where('destinatario_email', $user->email)
                      ->orWhere('destinatario_user_id', $user->id);
                })
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notificación no encontrada'
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notificación eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar notificación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get urgent notifications
     */
    public function getUrgentNotifications(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $urgentNotifications = NotificacionInterna::where(function($q) use ($user) {
                $q->where('destinatario_email', $user->email)
                  ->orWhere('destinatario_user_id', $user->id)
                  ->orWhere('tipo', 'caso_urgente');
            })
            ->where('tipo', 'caso_urgente')
            ->whereNull('leida_en')
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

            return response()->json([
                'success' => true,
                'data' => $urgentNotifications,
                'count' => $urgentNotifications->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener notificaciones urgentes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test notification system
     */
    public function testNotification(Request $request): JsonResponse
    {
        // Only admin can test notifications
        if (Auth::user()->role !== 'administrador') {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para probar notificaciones'
            ], 403);
        }

        try {
            $testData = [
                'tipo' => 'test',
                'titulo' => 'Prueba de Notificación',
                'mensaje' => 'Esta es una notificación de prueba del sistema Vital Red.',
                'destinatario_user_id' => Auth::id(),
                'destinatario_email' => Auth::user()->email,
                'enviado_por_user_id' => Auth::id()
            ];

            $notification = $this->notificationService->createInternalNotification($testData);

            if ($request->get('send_email', false)) {
                $this->notificationService->sendEmailNotification($notification);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notificación de prueba enviada exitosamente',
                'data' => $notification
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar notificación de prueba: ' . $e->getMessage()
            ], 500);
        }
    }
}
