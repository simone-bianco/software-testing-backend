<?php

namespace Tests\Feature\Whitebox;

use App\Exceptions\MaxCapacityExceededException;
use App\Models\Patient;
use App\Models\Reservation;
use App\Models\Structure;
use App\Models\Vaccine;
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
            $reservation = Reservation::findOrFail($reservation->id);
            $this->assertNotEquals(Reservation::COMPLETED_STATE, $reservation->state);
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

    /**
     * @throws MaxCapacityExceededException
     * @throws Throwable
     * @throws ValidationException
     */
    public function testCancelAndStockIncrementThrowsValidationException()
    {
        $reservation = $this->createReservation(Structure::firstOrFail(), Patient::firstOrFail());
        $reservation->state = "NOT_VALID_STATE";
        try {
            $this->reservationRepository->cancelAndStockIncrement($reservation);
            $this->fail('Eccezione di validazione non lanciata');
        } catch (ValidationException $validationException) {
            $this->assertNotNull($validationException);
            $reservation = Reservation::findOrFail($reservation->id);
            $this->assertNotEquals(Reservation::CANCELED_STATE, $reservation->state);
        }
    }

    /**
     * @throws MaxCapacityExceededException
     * @throws Throwable
     * @throws ValidationException
     */
    public function testCreateAndStockDecrementThrowsValidationException()
    {
        $reservation = ReservationFactory::new()->make([
            'patient_id' => 100,
            'stock_id' => 1,
            'date' => Carbon::now()->format('Y-m-d')
        ]);
        try {
            $this->reservationRepository->createAndStockDecrement($reservation);
            $this->fail('Eccezione di validazione non lanciata');
        } catch (ValidationException $validationException) {
            $this->assertNotNull($validationException);
            $this->assertDatabaseCount('reservations', 0);
        }
    }

    /**
     * @throws MaxCapacityExceededException
     * @throws Throwable
     * @throws ValidationException
     */
    public function testCreateAndStockDecrementThrowsException()
    {
        $reservation = ReservationFactory::new()->make([
            'patient_id' => 100,
            'stock_id' => '100',
            'date' => Carbon::now()->format('Y-m-d')
        ]);
        try {
            $this->reservationRepository->createAndStockDecrement($reservation);
            $this->fail('Eccezione di validazione non lanciata');
        } catch (ValidationException $validationException) {
            $this->fail('Lanciata eccezione errata');
        } catch (Exception $exception) {
            $this->assertNotNull($exception);
            $this->assertDatabaseCount('reservations', 0);
        }
    }

    /**
     * @throws MaxCapacityExceededException
     * @throws Throwable
     * @throws ValidationException
     */
    public function testCreateRecallAndStockDecrementThrowsValidationException()
    {
        $structure = Structure::firstOrFail();
        foreach ($structure->stocks as $stock) {
            $stock->quantity = 10;
            $stock->save();
        }
        $patient = Patient::firstOrFail();
        $reservation = $this->createReservation($structure, $patient);
        $this->reservationRepository->confirmAndSave($reservation);
        $reservation = $this->reservationRepository->completeAndSave($reservation);
        $this->assertEquals(Reservation::COMPLETED_STATE, $reservation->state);
        try {
            $this->reservationRepository->createRecallAndStockDecrement(
                $patient, Carbon::now()->subDays(2), '12:00', Vaccine::firstOrFail(), $structure
            );
            $this->fail('Eccezione di validazione non lanciata');
        } catch (ValidationException $validationException) {
            $this->assertNotNull($validationException);
            $this->assertDatabaseCount('reservations', 1);
        }
    }

    /**
     * @throws MaxCapacityExceededException
     * @throws Throwable
     * @throws ValidationException
     */
    public function testCreateRecallAndStockDecrementThrowsException()
    {
        $structure = Structure::firstOrFail();
        $patient = Patient::firstOrFail();
        $reservation = $this->createReservation($structure, $patient);
        $this->reservationRepository->confirmAndSave($reservation);
        $this->reservationRepository->completeAndSave($reservation);
        try {
            $this->reservationRepository->createRecallAndStockDecrement(
                $patient, Carbon::now()->format('Y-m-d'), '12:00', Vaccine::firstOrFail(), $structure
            );
            $this->fail('Eccezione di validazione non lanciata');
        } catch (ValidationException $validationException) {
            $this->fail('Lanciata eccezione errata');
        } catch (Exception $exception) {
            $this->assertNotNull($exception);
            $this->assertDatabaseCount('reservations', 1);
        }
    }

    /**
     * @throws MaxCapacityExceededException
     * @throws Throwable
     * @throws ValidationException
     */
    public function testChangeVaccineAndConfirmThrowsValidationException()
    {
        $structure = Structure::firstOrFail();
        $patient = Patient::firstOrFail();
        $reservation = $this->createReservation($structure, $patient);
        $this->reservationRepository->confirmAndSave($reservation);
        try {
            $this->reservationRepository->changeVaccineAndConfirm(
                $reservation, Vaccine::findOrFail(2)
            );
            $this->fail('Eccezione di validazione non lanciata');
        } catch (ValidationException $validationException) {
            $this->assertNotNull($validationException);
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
            $this->assertNotNull($validationException);
        } catch (Exception $exception) {
            $this->fail('Lanciata eccezione errata');
        }
    }
}
