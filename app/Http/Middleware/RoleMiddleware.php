<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $role
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }
            
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user is active
        if (!$user->is_active) {
            Auth::logout();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario inactivo. Contacte al administrador.'
                ], 403);
            }
            
            return redirect()->route('login')->with('error', 'Usuario inactivo. Contacte al administrador.');
        }

        // Check if user has the required role
        if (!$user->hasRole($role)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para acceder a este recurso'
                ], 403);
            }
            
            // Redirect based on user role
            $redirectRoute = match($user->role) {
                'medico' => 'medico.dashboard',
                'administrador' => 'admin.dashboard',
                default => 'dashboard'
            };
            
            return redirect()->route($redirectRoute)->with('error', 'No tiene permisos para acceder a esa sección.');
        }

        return $next($request);
    }
}
