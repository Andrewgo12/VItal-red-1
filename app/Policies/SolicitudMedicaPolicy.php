<?php

namespace App\Policies;

use App\Models\SolicitudMedica;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SolicitudMedicaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'administrador' || $user->role === 'medico';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SolicitudMedica $solicitudMedica): bool
    {
        // Administradores pueden ver todas las solicitudes
        if ($user->role === 'administrador') {
            return true;
        }

        // Médicos pueden ver solicitudes de su especialidad o asignadas a ellos
        if ($user->role === 'medico') {
            return $solicitudMedica->especialidad_sugerida === $user->especialidad ||
                   $solicitudMedica->medico_evaluador_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'administrador' || $user->role === 'medico';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SolicitudMedica $solicitudMedica): bool
    {
        // Administradores pueden actualizar cualquier solicitud
        if ($user->role === 'administrador') {
            return true;
        }

        // Médicos pueden actualizar solicitudes asignadas a ellos
        if ($user->role === 'medico') {
            return $solicitudMedica->medico_evaluador_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SolicitudMedica $solicitudMedica): bool
    {
        // Solo administradores pueden eliminar solicitudes
        return $user->role === 'administrador';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SolicitudMedica $solicitudMedica): bool
    {
        return $user->role === 'administrador';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SolicitudMedica $solicitudMedica): bool
    {
        return $user->role === 'administrador';
    }

    /**
     * Determine whether the user can evaluate the model.
     */
    public function evaluate(User $user, SolicitudMedica $solicitudMedica): bool
    {
        // Solo médicos pueden evaluar
        if ($user->role !== 'medico') {
            return false;
        }

        // La solicitud debe estar pendiente o en evaluación por este médico
        return $solicitudMedica->estado === 'pendiente_evaluacion' ||
               ($solicitudMedica->estado === 'en_evaluacion' && 
                $solicitudMedica->medico_evaluador_id === $user->id);
    }

    /**
     * Determine whether the user can assign the model.
     */
    public function assign(User $user, SolicitudMedica $solicitudMedica): bool
    {
        return $user->role === 'administrador';
    }

    /**
     * Determine whether the user can download attachments.
     */
    public function downloadAttachments(User $user, SolicitudMedica $solicitudMedica): bool
    {
        return $this->view($user, $solicitudMedica);
    }

    /**
     * Determine whether the user can export data.
     */
    public function export(User $user): bool
    {
        return $user->role === 'administrador' || $user->role === 'medico';
    }
}
