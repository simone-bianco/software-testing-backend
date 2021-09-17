<?php

namespace App\Observers;

use App\Models\Reservation;
use Carbon\Carbon;

class ReservationObserver
{
    public bool $afterCommit = true;

    protected function notifyUpdateToStructure(Reservation $reservation)
    {
        $structure = $reservation->stock->structure;
        $structure->last_reservation_update = Carbon::now()->format('Y-m-d H:i:s');
        $structure->save();
    }

    /**
     * @param  \App\Models\Reservation  $reservation
     * @return void
     */
    public function created(Reservation $reservation)
    {
        $this->notifyUpdateToStructure($reservation);
    }

    /**
     * Handle the Reservation "updated" event.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return void
     */
    public function updated(Reservation $reservation)
    {
        $this->notifyUpdateToStructure($reservation);
    }
}
