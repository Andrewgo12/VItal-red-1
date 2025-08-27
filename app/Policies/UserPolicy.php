<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'administrador';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Administradores pueden ver cualquier usuario
        if ($user->role === 'administrador') {
            return true;
        }

        // Los usuarios pueden ver su propio perfil
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'administrador';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Administradores pueden actualizar cualquier usuario
        if ($user->role === 'administrador') {
            return true;
        }

        // Los usuarios pueden actualizar su propio perfil
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Solo administradores pueden eliminar usuarios
        if ($user->role !== 'administrador') {
            return false;
        }

        // No se puede eliminar a sí mismo
        return $user->id !== $model->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->role === 'administrador';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->role === 'administrador' && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can manage roles.
     */
    public function manageRoles(User $user): bool
    {
        return $user->role === 'administrador';
    }

    /**
     * Determine whether the user can activate/deactivate users.
     */
    public function toggleStatus(User $user, User $model): bool
    {
        // Solo administradores pueden cambiar el estado
        if ($user->role !== 'administrador') {
            return false;
        }

        // No se puede desactivar a sí mismo
        return $user->id !== $model->id;
    }

    /**
     * Determine whether the user can view user statistics.
     */
    public function viewStatistics(User $user): bool
    {
        return $user->role === 'administrador';
    }

    /**
     * Determine whether the user can export user data.
     */
    public function export(User $user): bool
    {
        return $user->role === 'administrador';
    }

    /**
     * Determine whether the user can bulk manage users.
     */
    public function bulkManage(User $user): bool
    {
        return $user->role === 'administrador';
    }

    /**
     * Determine whether the user can impersonate other users.
     */
    public function impersonate(User $user, User $model): bool
    {
        // Solo administradores pueden impersonar
        if ($user->role !== 'administrador') {
            return false;
        }

        // No se puede impersonar a sí mismo
        if ($user->id === $model->id) {
            return false;
        }

        // No se puede impersonar a otros administradores
        return $model->role !== 'administrador';
    }
}
