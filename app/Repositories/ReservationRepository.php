<?php

namespace App\Repositories;

use App\Exceptions\MaxCapacityExceededException;
use App\Exceptions\NoAvailableStockException;
use App\Models\Patient;
use App\Models\Reservation;
use App\Models\Stock;
use App\Models\Structure;
use App\Models\Vaccine;
use App\Validators\ReservationValidator;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Jetstream;
use Log;
use Str;
use Throwable;

/**
 * Class ReservationRepository
 * @package App\Repositories
 */
class ReservationRepository
{
    protected ReservationValidator $reservationValidator;
    protected StockRepository $stockRepository;

    /**
     * ReservationRepository constructor.
     * @param  ReservationValidator  $reservationValidator
     * @param  StockRepository  $stockRepository
     */
    public function __construct(
        ReservationValidator $reservationValidator,
        StockRepository $stockRepository
    ) {
        $this->reservationValidator = $reservationValidator;
        $this->stockRepository = $stockRepository;
    }

    /**
     * @param  string  $code
     * @return Reservation
     */
    public function get(string $code): Reservation
    {
        try {
            $reservation = Reservation::where('code', '=', $code)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException($e->getMessage());
        }

        return $reservation;
    }

    /**
     * @param  Reservation  $reservation
     * @param  string  $notes
     * @return Reservation
     * @throws MaxCapacityExceededException
     * @throws ValidationException
     */
    public function confirmAndSave(Reservation $reservation, string $notes = ""): Reservation
    {
        try {
            $reservation->notes = $notes;
            $this->reservationValidator->canConfirm($reservation);

            $reservation->state = Reservation::CONFIRMED_STATE;
            return $this->assignAndSave(
                Reservation::whereId($reservation->id)->firstOrFail(),
                $reservation
            );
        } catch (ValidationException $validationException) {
            Log::error("Reservation confirmAndSave Validation:\n" . $validationException->getMessage());
            Log::error(print_r($validationException->errors(), true));
            throw $validationException;
        } catch (Exception $exception) {
            Log::error("Reservation confirmAndSave:\n" . $exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param  Reservation  $reservation
     * @return Reservation
     * @throws MaxCapacityExceededException
     * @throws ValidationException
     */
    public function completeAndSave(Reservation $reservation): Reservation
    {
        try {
            $this->reservationValidator->canComplete($reservation);

            $reservation->state = Reservation::COMPLETED_STATE;
            return $this->assignAndSave(
                Reservation::whereId($reservation->id)->firstOrFail(),
                $reservation
            );
        } catch (ValidationException $validationException) {
            Log::error("Reservation completeAndSave Validation:\n" . $validationException->getMessage());
            Log::error(print_r($validationException->errors(), true));
            throw $validationException;
        } catch (Exception $exception) {
            Log::error("Reservation completeAndSave:\n" . $exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param  Reservation  $reservation
     * @param  string  $notes
     * @return Reservation
     * @throws Throwable
     * @throws ValidationException
     */
    public function cancelAndStockIncrement(Reservation $reservation, string $notes = ''): Reservation
    {
        try {
            $this->reservationValidator->canCancel($reservation);

            DB::beginTransaction();

            $oldReservation = Reservation::whereId($reservation->id)->firstOrFail();

            $oldReservation->state = Reservation::CANCELED_STATE;
            $oldReservation->notes = $notes;
            $cancelledReservation = $this->save($oldReservation);

            $this->stockRepository->incrementAndSave($oldReservation->stock);

            DB::commit();

            return $cancelledReservation;
        } catch (ValidationException $validationException) {
            DB::rollBack();
            Log::error("Reservation cancelAndStockIncrement Validation:\n" . $validationException->getMessage());
            Log::error(print_r($validationException->errors(), true));
            throw $validationException;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error("Reservation cancelAndStockIncrement:\n" . $exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param  Reservation  $reservation
     * @param  string  $notes
     * @return Reservation
     * @throws MaxCapacityExceededException
     * @throws Throwable
     * @throws ValidationException
     */
    public function createAndStockDecrement(Reservation $reservation, string $notes = ''): Reservation
    {
        try {
            DB::beginTransaction();

            $this->reservationValidator->canCreate($reservation);
            $reservation->state = Reservation::PENDING_STATE;
            $reservation->notes = $notes;
            $newReservation = $this->assignAndSave(
                Reservation::factory()->make(),
                $reservation
            );

            $this->stockRepository->decrementAndSave($newReservation->stock);

            DB::commit();

            return $newReservation;
        } catch (ValidationException $validationException) {
            DB::rollBack();
            Log::error("Reservation createAndStockDecrement Validation:\n" . $validationException->getMessage());
            Log::error(print_r($validationException->errors(), true));
            throw $validationException;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error("Reservation createAndStockDecrement:\n" . $exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param  Patient  $patient
     * @param string|Carbon $date
     * @param string|Carbon $time
     * @param  Vaccine  $vaccine
     * @param  Structure  $structure
     * @return Reservation
     * @throws NoAvailableStockException
     * @throws Throwable
     * @throws ValidationException
     */
    public function createRecallAndStockDecrement(
        Patient $patient,
        $date,
        $time,
        Vaccine $vaccine,
        Structure $structure
    ): Reservation {
        try {
            DB::beginTransaction();

            $formattedDate = Carbon::make($date)->format('Y-m-d');
            $formattedTime = Carbon::make($time)->format('H:i');

            $stock = $structure->getMaxStock([$vaccine]);

            $newReservation = Reservation::make([
                'date' => $formattedDate,
                'time' => $formattedTime,
                'stock_id' => $stock->id,
                'code' => Str::random(32),
                'state' => Reservation::CONFIRMED_STATE,
                'patient_id' => $patient->id,
            ]);

            $this->reservationValidator->canCreate($newReservation);

            $newReservation = $this->save($newReservation);
            $this->stockRepository->decrementAndSave($newReservation->stock);

            DB::commit();

            return $newReservation;
        } catch (ValidationException $validationException) {
            DB::rollBack();
            Log::error("Reservation createAndStockDecrement Validation:\n" . $validationException->getMessage());
            Log::error(print_r($validationException->errors(), true));
            throw $validationException;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error("Reservation createAndStockDecrement:\n" . $exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param  Reservation  $reservation
     * @param  Vaccine  $newVaccine
     * @return Reservation
     * @throws MaxCapacityExceededException
     * @throws Throwable
     * @throws ValidationException
     */
    public function changeVaccineAndConfirm(Reservation $reservation, Vaccine $newVaccine): Reservation
    {
        try {
            $this->reservationValidator->canConfirm($reservation);

            DB::beginTransaction();

            $oldReservation = Reservation::whereId($reservation->id)->firstOrFail();

            $this->stockRepository->incrementAndSave($oldReservation->stock);

            $stock = $reservation->stock->structure->getMaxStock([$newVaccine]);
            $reservation->stock_id = $stock->id;
            $reservation->state = Reservation::CONFIRMED_STATE;

            $newReservation = $this->assignAndSave(
                $oldReservation,
                $reservation
            );

            $this->stockRepository->decrementAndSave($stock);

            DB::commit();

            return $newReservation;
        } catch (ValidationException $validationException) {
            DB::rollBack();
            Log::error("Reservation changeVaccineAndConfirm Validation:\n" . $validationException->getMessage());
            Log::error(print_r($validationException->errors(), true));
            throw $validationException;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error("Reservation changeVaccineAndConfirm:\n" . $exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param  Reservation  $newReservation
     * @param  Reservation  $reservation
     * @return Reservation
     * @throws ValidationException
     * @throws MaxCapacityExceededException
     */
    private function assignAndSave(Reservation $newReservation, Reservation $reservation): Reservation
    {
        $newReservation->code = $reservation->code;
        $newReservation->date = $reservation->date;
        $newReservation->time = $reservation->time
            ?? $reservation->stock->structure->getNextAvailableHour(Carbon::make($reservation->date));
        $newReservation->state = $reservation->state;
        $newReservation->patient_id = $reservation->patient_id;
        $newReservation->stock_id = $reservation->stock_id;
        $newReservation->code = $reservation->code ?? Str::random(32);
        $newReservation->notes = $reservation->notes;

        return $this->save($newReservation);
    }

    /**
     * @param  Reservation  $newReservation
     * @return Reservation
     * @throws ValidationException
     */
    protected function save(Reservation $newReservation): Reservation
    {
        $this->reservationValidator->validateData($newReservation->toArray(), ['reservation' => $newReservation]);
        $newReservation->save();

        return $newReservation;
    }
}
