<?php

namespace App\Policies;

use App\Models\Share;
use App\Models\Trip;
use App\Models\User;

class SharePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, ?Trip $trip = null): bool
    {
        // If a trip is provided, only the trip owner can view its shares.
        // If no trip is provided (class-level check), deny by default.
        if ($trip instanceof Trip) {
            return $trip->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Share $share): bool
    {
        // Owner of the trip can view the share
        return $share->trip->user_id === $user->id || $share->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ?Trip $trip = null): bool
    {
        // Require a trip to be provided and only allow the trip owner to create shares
        if (! $trip instanceof Trip) {
            return false;
        }

        return $trip->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Share $share): bool
    {
        // Only the user who created the share can delete it
        return $share->user_id === $user->id;
    }
}
