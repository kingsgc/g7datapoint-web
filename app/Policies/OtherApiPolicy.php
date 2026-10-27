<?php

namespace App\Policies;

use App\Models\OtherApi;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OtherApiPolicy
{
    use HandlesAuthorization;

    /**
     * The other_api table is a singleton credential store: every purchase
     * controller reads OtherApi::first(). Only the abilities needed to view,
     * create (fresh install only) and update that single row are granted.
     * Destructive abilities return false unconditionally so no role can delete
     * the row the purchase flow depends on.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_other_api::a::p::i');
    }

    public function view(User $user, OtherApi $otherApi): bool
    {
        return $user->can('view_other_api::a::p::i');
    }

    /**
     * Only used on a fresh database where other_api has no row yet.
     */
    public function create(User $user): bool
    {
        return $user->can('create_other_api::a::p::i');
    }

    public function update(User $user, OtherApi $otherApi): bool
    {
        return $user->can('update_other_api::a::p::i');
    }

    public function delete(User $user, OtherApi $otherApi): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, OtherApi $otherApi): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, OtherApi $otherApi): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, OtherApi $otherApi): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}