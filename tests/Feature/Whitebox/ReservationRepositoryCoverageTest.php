<?php

namespace Tests\Feature\Whitebox;

use App\Exceptions\MaxCapacityExceededException;
use App\Models\Patient;
use App\Models\Reservation;
use App\Models\Structure;
use Carbon\Carbon;
use Database\Factories\ReservationFactory;
use Exception;
use Illuminate\Validation\ValidationException;
use Str;
use Tests\ReservationTestCase;
use Throwable;

class ReservationRepositoryCoverageTest extends ReservationTestCase
{
    /**
     * @throws MaxCapacityExceededException
     * @throws Throwable
     * @throws ValidationException
     */
    public function testCompleteAndSaveThrowsValidationException()
    {
        $reservation = $this->createReservation(Structure::firstOrFail(), Patient::firstOrFail());
        $reservation->state = "NOT_VALID_STATE";
        try {
            $this->reservationRepository->completeAndSave($reservation);
            $this->fail('Eccezione di validazione non lanciata');
        } catch (ValidationException $validationException) {
            $this->assertNotNull($validationException);
        }
    }
    /**
     * @throws MaxCapacityExceededException
     * @throws Throwable
     * @throws ValidationException
     */
    public function testCompleteAndSaveThrowsException()
    {
        $structure = Structure::firstOrFail();
        $reservation = $this->createReservation($structure, Patient::firstOrFail());
        $reservation = $this->reservationRepository->confirmAndSave($reservation);
        $structure->capacity = 0;
        $structure->save();
        try {
            $this->reservationRepository->completeAndSave($reservation);
            $this->fail('Eccezione di validazione non lanciata');
        } catch (ValidationException $validationException) {
            $this->fail('Lanciata eccezione errata');
        } catch (Exception $exception) {
            $this->assertNotNull($exception);
        }
    }

    public function testGetThrowsException()
    {
        try {
            $this->reservationRepository->get(100);
            $this->fail('Eccezione di validazione non lanciata');
        } catch (Exception $exception) {
            $this->assertNotNull($exception);
        }
    }

    /**
     * @throws Throwable
     * @throws ValidationException
     * @throws MaxCapacityExceededException
     */
    public function testConfirmAndSaveThrowsValidationException()
    {
        $reservation = $this->createReservation(Structure::firstOrFail(), Patient::firstOrFail());
        try {
            $this->reservationRepository->confirmAndSave($reservation, Str::random(300));
            $this->fail('Eccezione di validazione non lanciata');
        } catch (ValidationException $validationException) {
            $this->assertNotNull($validationException);
        }
    }

    /**
     * @throws Throwable
     * @throws ValidationException
     * @throws MaxCapacityExceededException
     */
    public function testConfirmAndSaveThrowsException()
    {
        $reservation = $this->createReservation(Structure::firstOrFail(), Patient::firstOrFail());
        $reservation->stock_id = 100;
        try {
            $this->reservationRepository->confirmAndSave($reservation, Str::random(100));
            $this->fail('Eccezione di validazione non lanciata');
        } catch (ValidationException $validationException) {
            $this->fail('Lanciata eccezione errata');
        } catch (Exception $exception) {
            $this->assertNotNull($exception);
        }
    }
}
