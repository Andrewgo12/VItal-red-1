<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\AuditLog;
use Carbon\Carbon;

class UserManagementController extends Controller
{
    /**
     * Display user management dashboard
     */
    public function index()
    {
        $users = User::with(['solicitudesEvaluadas' => function($query) {
            $query->where('fecha_evaluacion', '>=', now()->subMonth());
        }])
        ->withCount([
            'solicitudesEvaluadas as evaluations_this_month' => function($query) {
                $query->where('fecha_evaluacion', '>=', now()->startOfMonth());
            }
        ])
        ->orderBy('created_at', 'desc')
        ->get();

        $statistics = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'medical_users' => User::where('role', 'medico')->count(),
            'admin_users' => User::where('role', 'administrador')->count(),
            'recent_logins' => User::where('last_login_at', '>=', now()->subDay())->count()
        ];

        $roles = [
            'administrador' => 'Administrador',
            'medico' => 'Médico Evaluador'
        ];

        $departments = [
            'urgencias' => 'Urgencias',
            'hospitalizacion' => 'Hospitalización',
            'consulta_externa' => 'Consulta Externa',
            'cirugia' => 'Cirugía',
            'uci' => 'UCI',
            'administracion' => 'Administración'
        ];

        return view('admin.users.index', compact('users', 'statistics', 'roles', 'departments'));
    }

    /**
     * Store a new user
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'role' => 'required|in:administrador,medico',
                'department' => 'nullable|string|max:100',
                'phone' => 'nullable|string|max:20',
                'medical_license' => 'nullable|string|max:50',
                'specialties' => 'nullable|array',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $userData = $request->only([
                'name', 'email', 'role', 'department', 'phone', 
                'medical_license', 'is_active'
            ]);

            $userData['password'] = Hash::make($request->password);
            $userData['is_active'] = $request->boolean('is_active', true);
            $userData['email_verified_at'] = now();
            
            if ($request->has('specialties')) {
                $userData['specialties'] = json_encode($request->specialties);
            }

            $user = User::create($userData);

            // Log the action
            AuditLog::logActivity(
                'create_user',
                'user',
                [
                    'created_user_id' => $user->id,
                    'created_user_email' => $user->email,
                    'created_user_role' => $user->role
                ],
                "Usuario creado: {$user->name} ({$user->email})"
            );

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user information
     */
    public function update(Request $request, User $user): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id)
                ],
                'role' => 'required|in:administrador,medico',
                'department' => 'nullable|string|max:100',
                'phone' => 'nullable|string|max:20',
                'medical_license' => 'nullable|string|max:50',
                'specialties' => 'nullable|array',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $originalData = $user->toArray();

            $userData = $request->only([
                'name', 'email', 'role', 'department', 'phone', 
                'medical_license', 'is_active'
            ]);

            $userData['is_active'] = $request->boolean('is_active', true);
            
            if ($request->has('specialties')) {
                $userData['specialties'] = json_encode($request->specialties);
            }

            $user->update($userData);

            // Log the action
            AuditLog::logActivity(
                'update_user',
                'user',
                [
                    'updated_user_id' => $user->id,
                    'updated_user_email' => $user->email,
                    'changes' => array_diff_assoc($userData, $originalData)
                ],
                "Usuario actualizado: {$user->name} ({$user->email})"
            );

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'data' => $user->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request, User $user): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $user->update([
                'password' => Hash::make($request->password),
                'password_changed_at' => now()
            ]);

            // Log the action
            AuditLog::logActivity(
                'change_user_password',
                'user',
                ['target_user_id' => $user->id],
                "Contraseña cambiada para usuario: {$user->name}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar contraseña: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(User $user): JsonResponse
    {
        try {
            $newStatus = !$user->is_active;
            $user->update(['is_active' => $newStatus]);

            $action = $newStatus ? 'activate_user' : 'deactivate_user';
            $message = $newStatus ? 'activado' : 'desactivado';

            // Log the action
            AuditLog::logActivity(
                $action,
                'user',
                [
                    'target_user_id' => $user->id,
                    'new_status' => $newStatus
                ],
                "Usuario {$message}: {$user->name}"
            );

            return response()->json([
                'success' => true,
                'message' => "Usuario {$message} exitosamente",
                'data' => ['is_active' => $newStatus]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado del usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user (soft delete)
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            // Prevent deletion of current user
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puede eliminar su propio usuario'
                ], 403);
            }

            // Check if user has evaluated medical requests
            $hasEvaluations = $user->solicitudesEvaluadas()->exists();
            
            if ($hasEvaluations) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar un usuario que ha evaluado solicitudes médicas'
                ], 403);
            }

            $userName = $user->name;
            $userEmail = $user->email;

            $user->delete();

            // Log the action
            AuditLog::logActivity(
                'delete_user',
                'user',
                [
                    'deleted_user_id' => $user->id,
                    'deleted_user_email' => $userEmail
                ],
                "Usuario eliminado: {$userName} ({$userEmail})"
            );

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user details
     */
    public function show(User $user): JsonResponse
    {
        try {
            $user->load([
                'solicitudesEvaluadas' => function($query) {
                    $query->latest()->take(10);
                }
            ]);

            // Get user statistics
            $statistics = [
                'total_evaluations' => $user->solicitudesEvaluadas()->count(),
                'evaluations_this_month' => $user->solicitudesEvaluadas()
                    ->where('fecha_evaluacion', '>=', now()->startOfMonth())->count(),
                'accepted_cases' => $user->solicitudesEvaluadas()
                    ->where('decision_medica', 'aceptar')->count(),
                'rejected_cases' => $user->solicitudesEvaluadas()
                    ->where('decision_medica', 'rechazar')->count(),
                'avg_response_time' => $this->calculateAverageResponseTime($user),
                'last_activity' => $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Nunca'
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'statistics' => $statistics
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalles del usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user activity report
     */
    public function activityReport(User $user, Request $request): JsonResponse
    {
        try {
            $startDate = Carbon::parse($request->get('start_date', now()->subMonth()));
            $endDate = Carbon::parse($request->get('end_date', now()));

            // Get evaluations in date range
            $evaluations = $user->solicitudesEvaluadas()
                ->whereBetween('fecha_evaluacion', [$startDate, $endDate])
                ->with('solicitudMedica')
                ->get();

            // Get audit logs for this user
            $auditLogs = AuditLog::where('user_id', $user->id)
                ->whereBetween('timestamp', [$startDate, $endDate])
                ->orderBy('timestamp', 'desc')
                ->take(50)
                ->get();

            // Calculate daily activity
            $dailyActivity = $evaluations->groupBy(function ($item) {
                return $item->fecha_evaluacion->format('Y-m-d');
            })->map->count();

            // Calculate performance metrics
            $performanceMetrics = [
                'total_evaluations' => $evaluations->count(),
                'accepted_rate' => $evaluations->count() > 0 ? 
                    round(($evaluations->where('decision_medica', 'aceptar')->count() / $evaluations->count()) * 100, 2) : 0,
                'avg_response_time' => $this->calculateAverageResponseTimeForCollection($evaluations),
                'urgent_cases_handled' => $evaluations->filter(function($eval) {
                    return $eval->solicitudMedica && $eval->solicitudMedica->prioridad_ia === 'Alta';
                })->count()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'start' => $startDate->format('d/m/Y'),
                        'end' => $endDate->format('d/m/Y')
                    ],
                    'evaluations' => $evaluations,
                    'audit_logs' => $auditLogs,
                    'daily_activity' => $dailyActivity,
                    'performance_metrics' => $performanceMetrics
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte de actividad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update users
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
                'action' => 'required|in:activate,deactivate,change_role',
                'role' => 'required_if:action,change_role|in:administrador,medico'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $userIds = $request->user_ids;
            $action = $request->action;
            $updatedCount = 0;

            foreach ($userIds as $userId) {
                // Skip current user for certain actions
                if ($userId == auth()->id() && in_array($action, ['deactivate'])) {
                    continue;
                }

                $user = User::find($userId);
                if (!$user) continue;

                switch ($action) {
                    case 'activate':
                        $user->update(['is_active' => true]);
                        break;
                    case 'deactivate':
                        $user->update(['is_active' => false]);
                        break;
                    case 'change_role':
                        $user->update(['role' => $request->role]);
                        break;
                }

                $updatedCount++;
            }

            // Log bulk action
            AuditLog::logActivity(
                'bulk_update_users',
                'user',
                [
                    'action' => $action,
                    'user_ids' => $userIds,
                    'updated_count' => $updatedCount
                ],
                "Actualización masiva de usuarios: {$action} aplicado a {$updatedCount} usuarios"
            );

            return response()->json([
                'success' => true,
                'message' => "Acción aplicada a {$updatedCount} usuarios exitosamente"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en actualización masiva: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate average response time for a user
     */
    private function calculateAverageResponseTime(User $user): float
    {
        $evaluations = $user->solicitudesEvaluadas()
            ->whereNotNull('fecha_evaluacion')
            ->with('solicitudMedica')
            ->get();

        if ($evaluations->isEmpty()) {
            return 0;
        }

        $totalHours = $evaluations->sum(function ($evaluation) {
            if (!$evaluation->solicitudMedica) return 0;
            return $evaluation->solicitudMedica->fecha_recepcion_email
                ->diffInHours($evaluation->fecha_evaluacion);
        });

        return round($totalHours / $evaluations->count(), 2);
    }

    /**
     * Calculate average response time for a collection of evaluations
     */
    private function calculateAverageResponseTimeForCollection($evaluations): float
    {
        if ($evaluations->isEmpty()) {
            return 0;
        }

        $totalHours = $evaluations->sum(function ($evaluation) {
            if (!$evaluation->solicitudMedica) return 0;
            return $evaluation->solicitudMedica->fecha_recepcion_email
                ->diffInHours($evaluation->fecha_evaluacion);
        });

        return round($totalHours / $evaluations->count(), 2);
    }

    /**
     * Export users data
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');
        
        $users = User::with(['solicitudesEvaluadas'])
            ->withCount('solicitudesEvaluadas')
            ->get();

        if ($format === 'csv') {
            $filename = 'usuarios_' . now()->format('Y-m-d_H-i-s') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($users) {
                $file = fopen('php://output', 'w');
                
                // CSV headers
                fputcsv($file, [
                    'ID', 'Nombre', 'Email', 'Rol', 'Departamento', 
                    'Teléfono', 'Licencia Médica', 'Estado', 'Evaluaciones Realizadas',
                    'Último Acceso', 'Fecha Creación'
                ]);
                
                // Data rows
                foreach ($users as $user) {
                    fputcsv($file, [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->role,
                        $user->department ?? '',
                        $user->phone ?? '',
                        $user->medical_license ?? '',
                        $user->is_active ? 'Activo' : 'Inactivo',
                        $user->solicitudes_evaluadas_count,
                        $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : '',
                        $user->created_at->format('d/m/Y H:i')
                    ]);
                }
                
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return response()->json([
            'success' => false,
            'message' => 'Formato de exportación no soportado'
        ], 400);
    }
}
