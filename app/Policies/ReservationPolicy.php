<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class ReservationPolicy
 * @package App\Policies
 */
class ReservationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->responsible()->exists();
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Reservation  $reservation
     * @return mixed
     */
    public function view(User $user, Reservation $reservation)
    {
        if ($user->responsible()->exists()) {
            return $user->responsible->structure->id === $reservation->stock->structure->id;
        }

        return false;
    }

    /**
     * @param  User  $user
     * @param  Reservation  $reservation
     * @return bool
     */
    public function create(User $user, Reservation $reservation)
    {
        if ($user->responsible()->exists()) {
            return $user->responsible->structure->id === $reservation->stock->structure->id;
        }

        return false;
    }

    /**
     * @param  User  $user
     * @param  Structure  $structure
     * @return bool
     */
    public function store(User $user, Structure $structure)
    {
        if ($user->responsible()->exists()) {
            return $user->responsible->structure->id === $structure->id;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Reservation  $reservation
     * @return mixed
     */
    public function update(User $user, Reservation $reservation)
    {
        if ($user->responsible()->exists()) {
            return $user->responsible->structure->id === $reservation->stock->structure->id;
        }

        return false;
    }

    /**
     * Determine whether the
     * @param  \App\Models\User  $user
     * @param  \App\Models\Structure  $structure
     * @return mixed
     */
    public function poll(User $user, Structure  $structure)
    {
        if ($user->responsible()->exists()) {
            return $user->responsible->structure->id === $structure->id;
        }

        return false;
    }
}
