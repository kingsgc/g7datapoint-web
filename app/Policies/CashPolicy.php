<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Cash;
use Illuminate\Auth\Access\HandlesAuthorization;

class CashPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_airtime::cash');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Cash $cash): bool
    {
        return $user->can('view_airtime::cash');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_airtime::cash');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Cash $cash): bool
    {
        return $user->can('update_airtime::cash');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Cash $cash): bool
    {
        return $user->can('delete_airtime::cash');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_airtime::cash');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Cash $cash): bool
    {
        return $user->can('force_delete_airtime::cash');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_airtime::cash');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Cash $cash): bool
    {
        return $user->can('restore_airtime::cash');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_airtime::cash');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Cash $cash): bool
    {
        return $user->can('replicate_airtime::cash');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_airtime::cash');
    }
}
