<?php

namespace App\Policies;

use App\Models\Call;
use App\Models\User;

class CallPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Call $call): bool
    {
        return $call->involves($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function accept(User $user, Call $call): bool
    {
        return $call->recipient_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function decline(User $user, Call $call): bool
    {
        return $call->recipient_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function cancel(User $user, Call $call): bool
    {
        return $call->caller_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function end(User $user, Call $call): bool
    {
        return $call->involves($user);
    }
}
