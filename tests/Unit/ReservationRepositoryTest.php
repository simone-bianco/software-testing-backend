<?php

namespace Tests\Unit;

use App\Models\Patient;
use App\Models\Reservation;
use App\Models\Stock;
use App\Repositories\ReservationRepository;
use App\Repositories\StockRepository;
use App\Validators\ReservationValidator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Mockery;
use Tests\BaseTestCase;

class ReservationRepositoryTest extends BaseTestCase
{
    public function testUnitCreateReservation()
    {
        //creazione mock di StockRepository
        $this->instance(
            StockRepository::class,
            Mockery::mock(StockRepository::class, function (Mockery\MockInterface $mock) {
                $mock->shouldReceive('decrementAndSave')
                    ->once()
                    ->withArgs(fn($stock) => true);
            })
        );
        //creazione mock di ReservationValidator
        $this->instance(
            ReservationValidator::class,
            Mockery::mock(ReservationValidator::class, function (Mockery\MockInterface $mock) {
                $mock->shouldReceive('validateData')
                    ->once()
                    ->withArgs(fn($newReservation, $extendParams) => true);
                $mock->shouldReceive('canCreate')
                    ->once()
                    ->withArgs(fn($reservation) => true);
            })
        );

        $reservation = $this->getTestReservation();

        $reservationRepository = app(ReservationRepository::class);

        $createdReservation = $reservationRepository->createAndStockDecrement($reservation, 'test notes');
        $this->assertInstanceOf(Reservation::class, $createdReservation);
        $this->assertNotNull($createdReservation->id);
        $this->assertNotNull(Reservation::find($createdReservation->id));
        $this->assertEquals(Reservation::PENDING_STATE, $createdReservation->state);
        $this->assertEquals('test notes', $createdReservation->notes);
    }

    public function testUnitConfirmReservation()
    {
        $this->instance(
            ReservationValidator::class,
            Mockery::mock(ReservationValidator::class, function (Mockery\MockInterface $mock) {
                $mock->shouldReceive('validateData')
                    ->once()
                    ->withArgs(fn($newReservation, $extendParams) => true);
                $mock->shouldReceive('canConfirm')
                    ->once()
                    ->withArgs(fn($reservation) => true);
            })
        );

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

    public function testUnitCancelReservation()
    {
        $this->instance(
            StockRepository::class,
            Mockery::mock(StockRepository::class, function (Mockery\MockInterface $mock) {
                $mock->shouldReceive('incrementAndSave')
                    ->once()
                    ->withArgs(function ($stock) {
                        $stock->quantity++;
                        $stock->save();
                        return true;
                    });
            })
        );
        $this->instance(
            ReservationValidator::class,
            Mockery::mock(ReservationValidator::class, function (Mockery\MockInterface $mock) {
                $mock->shouldReceive('validateData')
                    ->once()
                    ->withArgs(fn($newReservation, $extendParams) => true);
                $mock->shouldReceive('canCancel')
                    ->once()
                    ->withArgs(fn($reservation) => true);
            })
        );

        $reservation = $this->createTestReservation([
            'notes' => 'test notes',
            'state' => Reservation::CONFIRMED_STATE,
            'code' => Str::random(32)
        ]);
        $qty = $reservation->stock->quantity;

        $reservationRepository = app(ReservationRepository::class);

        $cancelledReservation = $reservationRepository->cancelAndStockIncrement($reservation, 'test notes 3');

        $this->assertInstanceOf(Reservation::class, $cancelledReservation);
        $this->assertNotNull($cancelledReservation->id);
        $this->assertEquals(Reservation::CANCELED_STATE, $cancelledReservation->state);
        $this->assertEquals('test notes 3', $cancelledReservation->notes);
        $this->assertEquals($qty + 1, $cancelledReservation->stock->quantity);
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
