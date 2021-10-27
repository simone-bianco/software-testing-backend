<?php

namespace App\Validators;

use App\Models\Reservation;
use App\Models\Structure;
use App\Rules\ReservationDateIsValid;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReservationValidator extends EntityValidator
{
    /**
     * @param $extendParameters
     * @return array
     */
    protected function getRules($extendParameters): array
    {
        /** @var Reservation $reservation */
        $reservation = Arr::get($extendParameters, 'reservation') ?? Reservation::factory()->newModel();

        return [
            'date' => ['required', 'date_format:Y-m-d', new ReservationDateIsValid($reservation)],
            'state'=> ['required', Rule::in(Reservation::getStates())],
            'time' => ['required', 'date_format:H:i', 'after_or_equal:08:00', 'before_or_equal:20:00'],
            'notes' => ['max:255', config('validation.notes')],
            'code' => [
                'required',
                config('validation.reservation_code'),
                Rule::unique('reservations', 'code')->ignoreModel($reservation)
            ],
            'patient_id' => ['required', Rule::exists('patients', 'id')],
            'stock_id' => ['required', Rule::exists('stocks', 'id')],
        ];
    }

    /**
     * @param  Reservation  $reservation
     * @throws ValidationException
     */
    public function canConfirm(Reservation $reservation)
    {
        Validator::make($reservation->toArray(), [
            'notes' => ['max:255', config('validation.notes')],
            'state' => ['required', 'string', Rule::in([Reservation::PENDING_STATE])],
        ], ['state.in' => sprintf('La prenotazione è già %s', Reservation::stateToLabel($reservation->state))])
            ->validate();
    }

    /**
     * @param  Reservation  $reservation
     * @throws ValidationException
     */
    public function canComplete(Reservation $reservation)
    {
        Validator::make($reservation->toArray(), [
            'state' => ['required', 'string', Rule::in([Reservation::CONFIRMED_STATE])],
        ], ['state.in' => sprintf('La prenotazione è già %s', Reservation::stateToLabel($reservation->state))])->validate();
    }

    /**
     * @param  Reservation  $reservation
     * @throws ValidationException
     */
    public function canCancel(Reservation $reservation)
    {
        Validator::make($reservation->toArray(), [
            'state' => ['required', 'string', Rule::in([Reservation::PENDING_STATE, Reservation::CONFIRMED_STATE])],
            'notes' => ['max:255', config('validation.notes')],
            ],
            ['state.in' => sprintf('La prenotazione è già %s', Reservation::stateToLabel($reservation->state))]
        )->validate();
    }

    /**
     * @param  Reservation  $reservation
     * @throws ValidationException
     */
    public function canCreate(Reservation $reservation)
    {
        $activeReservations = Reservation::where('patient_id', '=', $reservation->patient_id)
            ->whereNotIn('state', [Reservation::CANCELED_STATE, Reservation::COMPLETED_STATE])
            ->get();

        Validator::validate(['state' => $activeReservations->count()], [
            'state' => ['in:0']
        ], ['state.in' => "E' già registrata una prenotazione attiva"]);
    }
}
