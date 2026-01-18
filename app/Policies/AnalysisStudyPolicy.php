<?php

namespace App\Policies;

use App\Models\AnalysisStudy;
use App\Models\User;

class AnalysisStudyPolicy
{
    /**
     * Determine whether the user can view the study.
     * Users can view their own studies or public studies.
     * Denies access to soft-deleted studies.
     */
    public function view(?User $user, AnalysisStudy $study): bool
    {
        // Deny access to soft-deleted studies
        if ($study->trashed()) {
            return false;
        }

        // Public studies are viewable by anyone
        if ($study->is_public) {
            return true;
        }

        // Private studies require ownership
        return $user && $user->id === $study->user_id;
    }

    /**
     * Determine whether the user can update the study.
     * Denies access to soft-deleted studies.
     */
    public function update(User $user, AnalysisStudy $study): bool
    {
        // Cannot update soft-deleted studies
        if ($study->trashed()) {
            return false;
        }

        return $user->id === $study->user_id;
    }

    /**
     * Determine whether the user can delete the study.
     * Denies access to already soft-deleted studies.
     */
    public function delete(User $user, AnalysisStudy $study): bool
    {
        // Cannot delete already soft-deleted studies
        if ($study->trashed()) {
            return false;
        }

        return $user->id === $study->user_id;
    }

    /**
     * Determine whether the user can restore the study.
     */
    public function restore(User $user, AnalysisStudy $study): bool
    {
        return $user->id === $study->user_id;
    }

    /**
     * Determine whether the user can permanently delete the study.
     */
    public function forceDelete(User $user, AnalysisStudy $study): bool
    {
        return $user->id === $study->user_id;
    }
}
