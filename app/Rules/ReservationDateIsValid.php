<?php

namespace App\Rules;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;

class ReservationDateIsValid implements Rule
{
    protected Reservation $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $date = Carbon::make($value);

        $structure = $this->reservation->stock->structure;
        $busyDates = $structure->getBusyDates();

        $oldReservation = Reservation::whereId($this->reservation->id)->first();

        if (!$oldReservation) {
            if (in_array($this->reservation->date->format('Y-m-d'), $busyDates)) {
                return false;
            }
            if ($date->lessThanOrEqualTo(Carbon::now())) {
                return false;
            }
            return true;
        }

        if (strcmp($this->reservation->state, Reservation::CANCELED_STATE)
            && strcmp($this->reservation->state, Reservation::COMPLETED_STATE)) {
            if ($date->lessThanOrEqualTo(Carbon::now())) {
                return false;
            }
            if (in_array($this->reservation->date->format('Y-m-d'), $busyDates)
                && $this->reservation->date->getTimestamp() != $oldReservation->date->getTimestamp()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Data della prenotazione non valida';
    }
}
