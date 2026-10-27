<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AppDownload;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppDownloadPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_app::download');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AppDownload $appDownload): bool
    {
        return $user->can('view_app::download');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_app::download');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AppDownload $appDownload): bool
    {
        return $user->can('update_app::download');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AppDownload $appDownload): bool
    {
        return $user->can('delete_app::download');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_app::download');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, AppDownload $appDownload): bool
    {
        return $user->can('force_delete_app::download');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_app::download');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, AppDownload $appDownload): bool
    {
        return $user->can('restore_app::download');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_app::download');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, AppDownload $appDownload): bool
    {
        return $user->can('replicate_app::download');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_app::download');
    }
}
