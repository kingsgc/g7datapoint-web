<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VirtualAccount;
use Illuminate\Auth\Access\HandlesAuthorization;

class VirtualAccountPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_virtual::account');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, VirtualAccount $virtualAccount): bool
    {
        return $user->can('view_virtual::account');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_virtual::account');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VirtualAccount $virtualAccount): bool
    {
        return $user->can('update_virtual::account');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VirtualAccount $virtualAccount): bool
    {
        return $user->can('delete_virtual::account');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_virtual::account');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, VirtualAccount $virtualAccount): bool
    {
        return $user->can('force_delete_virtual::account');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_virtual::account');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, VirtualAccount $virtualAccount): bool
    {
        return $user->can('restore_virtual::account');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_virtual::account');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, VirtualAccount $virtualAccount): bool
    {
        return $user->can('replicate_virtual::account');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_virtual::account');
    }
}
