<?php

namespace Tests\Feature\Blackbox;

use App\Models\Patient;
use App\Models\Reservation;
use App\Models\Stock;
use App\Repositories\ReservationRepository;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\BaseTestCase;

/**
 * @group reservationIntegration
 */
class ReservationRepositoryFullIntegrationTest extends BaseTestCase
{
    public function testValidatorStockRepositoryIntegrationCreateReservation()
    {
        $reservation = $this->getTestReservation();

        $reservationRepository = app(ReservationRepository::class);

        $createdReservation = $reservationRepository->createAndStockDecrement($reservation, 'test notes');
        $this->assertInstanceOf(Reservation::class, $createdReservation);
        $this->assertNotNull($createdReservation->id);
        $this->assertEquals(Reservation::PENDING_STATE, $createdReservation->state);
        $this->assertEquals('test notes', $createdReservation->notes);
    }

    public function testValidatorStockRepositoryIntegrationConfirmReservation()
    {
        $reservation = $this->createTestReservation([
            'notes' => 'test notes',
            'state' => Reservation::PENDING_STATE,
            'code' => Str::random(32)
        ]);

        $reservationRepository = app(ReservationRepository::class);

        $confirmedReservation = $reservationRepository->confirmAndSave($reservation, 'test notes 2');
        $this->assertInstanceOf(Reservation::class, $confirmedReservation);
        $this->assertNotNull($confirmedReservation->id);
        $this->assertEquals(Reservation::CONFIRMED_STATE, $confirmedReservation->state);
        $this->assertEquals('test notes 2', $confirmedReservation->notes);
    }

    public function testValidatorStockRepositoryCancelReservation()
    {
        $reservation = $this->createTestReservation([
            'notes' => 'test notes',
            'state' => Reservation::PENDING_STATE,
            'code' => Str::random(32)
        ]);

        $reservationRepository = app(ReservationRepository::class);

        $cancelledReservation = $reservationRepository->cancelAndStockIncrement($reservation, 'test notes 3');
        $this->assertInstanceOf(Reservation::class, $cancelledReservation);
        $this->assertNotNull($cancelledReservation->id);
        $this->assertEquals(Reservation::CANCELED_STATE, $cancelledReservation->state);
        $this->assertEquals('test notes 3', $cancelledReservation->notes);
    }

    protected function getTestReservation(): Reservation
    {
        return Reservation::make($this->getTestReservationData());
    }

    protected function createTestReservation(array $override = []): Reservation
    {
        return Reservation::create(array_merge($this->getTestReservationData(), $override));
    }

    protected function getTestReservationData(): array
    {
        return [
            'date' => Carbon::now()->addDays(5),
            'time' => '12:00',
            'patient_id' => Patient::firstOrFail()->id,
            'stock_id' => Stock::firstOrFail()->id,
        ];
    }
}
