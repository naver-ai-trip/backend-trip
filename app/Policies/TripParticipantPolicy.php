<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;

class TripParticipantPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, ?Trip $trip = null): bool
    {
        // When called from Filament without a specific trip (navigation menu, resource index)
        // allow all authenticated users to access the index page
        if ($trip === null) {
            return true;
        }

        // When viewing participants of a specific trip,
        // only trip owner OR participants can view trip participants
        return $trip->user_id === $user->id ||
               $trip->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TripParticipant $tripParticipant): bool
    {
        // Trip owner OR participant can view participant details
        $trip = $tripParticipant->trip;
        return $trip->user_id === $user->id ||
               $trip->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All authenticated users can add participants to their trips
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TripParticipant $tripParticipant): bool
    {
        // Cannot change owner role
        if ($tripParticipant->role === 'owner') {
            return false;
        }

        // Only trip owner can update participant roles
        $trip = $tripParticipant->trip;
        return $trip->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TripParticipant $tripParticipant): bool
    {
        // Cannot remove owner
        if ($tripParticipant->role === 'owner') {
            return false;
        }

        $trip = $tripParticipant->trip;

        // Trip owner can remove any participant OR participant can leave themselves
        return $trip->user_id === $user->id || $tripParticipant->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TripParticipant $tripParticipant): bool
    {
        $trip = $tripParticipant->trip;
        return $trip->user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TripParticipant $tripParticipant): bool
    {
        $trip = $tripParticipant->trip;
        return $trip->user_id === $user->id;
    }
}
