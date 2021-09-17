<?php

namespace Tests\Feature;

use App\Helper\PatientCreator;
use App\Models\Reservation;
use App\Models\Responsible;
use App\Models\Stock;
use App\Models\Structure;
use App\Models\Vaccine;
use App\Repositories\ReservationRepository;
use Artisan;
use Carbon\Carbon;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ResponsibleHandleReservationTest extends TestCase
{
    use RefreshDatabase;

    protected ReservationRepository $reservationRepository;
    protected PatientCreator $patientCreator;

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Throwable
     */
    public function setUp() : void {
        parent::setUp();
        $this->reservationRepository = $this->app->make(ReservationRepository::class);
        $this->patientCreator = $this->app->make(PatientCreator::class);
        $dbSeeder = $this->app->make(TestDatabaseSeeder::class);
        $this->refreshDatabase();
        Artisan::call('migrate:refresh');
        $dbSeeder->run();
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function testAcceptReservation()
    {
        $this->withoutExceptionHandling();

        $structure = Structure::firstOrFail();

        /** @var Responsible $responsible */
        $responsible = $structure->responsibles()->firstOrFail();

        $reservation = $this->getPendingReservationOrCreate($structure);
        $stock = $reservation->stock;

        $this->be($responsible->account->user);
        $response = $this->call(
            'PUT',
            "/prenotazione/{$reservation->id}/update",
            array_merge(
                $reservation->toArray(),
                ['vaccine' => $stock->batch->vaccine->name, 'state' => Reservation::CONFIRMED_STATE]
            ));
        $response->assertStatus(Response::HTTP_FOUND);

        $updatedReservation = $this->reservationRepository->get($reservation->code);
        $this->assertEquals(Reservation::CONFIRMED_STATE, $updatedReservation->state);
        $this->reservationsAreEqual($reservation, $updatedReservation);
        $this->assertEquals($reservation->stock_id, $updatedReservation->stock_id);

        $this->stockIsSameAfterConfirm($stock);
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function testAcceptReservationAndChangeVaccine()
    {
        $this->withoutExceptionHandling();

        $structure = Structure::firstOrFail();

        /** @var Responsible $responsible */
        $responsible = $structure->responsibles()->firstOrFail();

        $reservation = $this->getPendingReservationOrCreate($structure);
        $stock = $reservation->stock;

        $anotherVaccine = Vaccine::where('id', '!=', $stock->batch->vaccine->id)
            ->firstOrFail();

        /** @var Stock $differentAvailableStock */
        $differentAvailableStock = $structure->getMaxStock([$anotherVaccine]);
        $differentAvailableStockQty = $differentAvailableStock->quantity;

        $this->be($responsible->account->user);
        $response = $this->call(
            'PUT',
            "/prenotazione/{$reservation->id}/update",
            array_merge(
                $reservation->toArray(),
                ['vaccine' => $anotherVaccine->name, 'state' => Reservation::CONFIRMED_STATE]
            ));
        $response->assertStatus(Response::HTTP_FOUND);

        $updatedReservation = $this->reservationRepository->get($reservation->code);
        $this->assertEquals(Reservation::CONFIRMED_STATE, $updatedReservation->state);
        $this->assertEquals($differentAvailableStock->batch->vaccine->name, $updatedReservation->stock->batch->vaccine->name);
        $this->reservationsAreEqual($reservation, $updatedReservation);
        $this->assertEquals($differentAvailableStock->id, $updatedReservation->stock_id);

        $updatedDifferentAvailableStock = Stock::findOrFail($differentAvailableStock->id);
        $updatedStock = Stock::findOrFail($stock->id);
        $this->assertEquals($differentAvailableStockQty - 1, $updatedDifferentAvailableStock->quantity);
        $this->assertEquals($stock->quantity + 1, $updatedStock->quantity);
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function testCompleteReservationAndCreateRecall()
    {
        $this->withoutExceptionHandling();

        $structure = Structure::firstOrFail();

        /** @var Responsible $responsible */
        $responsible = $structure->responsibles()->firstOrFail();

        $newReservation = $this->getPendingReservationOrCreate($structure);
        $reservation = $this->reservationRepository->confirmAndSave($newReservation);

        $this->be($responsible->account->user);
        $response = $this->call(
            'PUT',
            "/prenotazione/{$reservation->id}/update",
            array_merge(
                $reservation->toArray(),
                ['vaccine' => $reservation->stock->batch->vaccine->name, 'state' => Reservation::COMPLETED_STATE]
            ));
        $response->assertStatus(Response::HTTP_FOUND);

        $updatedReservation = $this->reservationRepository->get($reservation->code);
        $this->assertEquals(Reservation::COMPLETED_STATE, $updatedReservation->state);
        $this->reservationsAreEqual($reservation, $updatedReservation);
        $this->assertEquals($reservation->stock_id, $updatedReservation->stock_id);

        $this->stockIsSameAfterComplete($reservation->stock);

        /** @var Vaccine $recallVaccine */
        $recallVaccine = $structure->stocks()->where('quantity', '>', 0)->firstOrFail()->batch->vaccine;
        $recallStock = $structure->getMaxStock([$recallVaccine]);
        $recall = $this->reservationRepository->createRecallAndStockDecrement(
            $updatedReservation->patient,
            Carbon::now()->addDays(100),
            "12:00",
            $recallVaccine,
            $structure
        );

        $this->assertEquals(Reservation::CONFIRMED_STATE, $recall->state);
        $this->assertEquals($recallVaccine->name, $recall->stock->batch->vaccine->name);
        $this->assertEquals($updatedReservation->patient_id, $recall->patient_id);
        $this->assertEquals($recallStock->id, $recall->stock_id);
        $this->assertEquals("12:00", $recall->time->format('H:i'));
        $this->assertEquals(Carbon::now()->addDays(100)->format('Y-m-d'), $recall->date->format('Y-m-d'));

        $updatedRecallStock = Stock::whereId($recallStock->id)->firstOrFail();

        $this->assertEquals($recallStock->quantity - 1, $updatedRecallStock->quantity);
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function testRefuseReservation()
    {
        $this->withoutExceptionHandling();

        $structure = Structure::firstOrFail();

        /** @var Responsible $responsible */
        $responsible = $structure->responsibles()->firstOrFail();

        $reservation = $this->getPendingReservationOrCreate($structure);

        $this->be($responsible->account->user);
        $response = $this->call(
            'PUT',
            "/prenotazione/{$reservation->id}/update",
            array_merge(
                $reservation->toArray(),
                ['vaccine' => $reservation->stock->batch->vaccine->name, 'state' => Reservation::CANCELED_STATE]
            ));
        $response->assertStatus(Response::HTTP_FOUND);

        $updatedReservation = $this->reservationRepository->get($reservation->code);
        $this->assertEquals(Reservation::CANCELED_STATE, $updatedReservation->state);
        $this->reservationsAreEqual($reservation, $updatedReservation);
        $this->assertEquals($reservation->stock_id, $updatedReservation->stock_id);

        $this->stockIsUpdatedAfterCancel($reservation->stock);
    }

    protected function reservationsAreEqual(Reservation $reservation, Reservation $updatedReservation)
    {
        $this->assertEquals($reservation->id, $updatedReservation->id);
        $this->assertEquals($reservation->patient_id, $updatedReservation->patient_id);
        $this->assertEquals($reservation->date->format('Y-m-d'), $updatedReservation->date->format('Y-m-d'));
        $this->assertEquals($reservation->time, $updatedReservation->time);
    }

    /**
     * @param  Structure  $structure
     * @return Reservation
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    protected function getPendingReservationOrCreate(Structure $structure): Reservation
    {
        /** @var Stock $availableStock */
        $availableStock = $structure->stocks()->where('quantity', '>', 0)->firstOrFail();

        /** @var Reservation $reservation */
        $reservation = $availableStock
            ->reservations()
            ->where('state', '=', Reservation::PENDING_STATE)
            ->first();

        if (!$reservation) {
            $newPatient = $this->patientCreator->execute();
            return $this->reservationRepository->createAndStockDecrement(
                Reservation::factory()->make([
                    'date' => Carbon::now()->addDay(),
                    'patient_id' => $newPatient->id,
                    'stock_id' => $availableStock->id
                ])
            );
        }

        return $reservation;
    }

    protected function stockIsSameAfterConfirm(Stock $stock)
    {
        $updatedStock = Stock::findOrFail($stock->id);
        $this->assertEquals($stock->quantity, $updatedStock->quantity);
    }

    protected function stockIsSameAfterComplete(Stock $stock)
    {
        $updatedStock = Stock::findOrFail($stock->id);
        $this->assertEquals($stock->quantity, $updatedStock->quantity);
    }

    protected function stockIsUpdatedAfterCancel(Stock $stock)
    {
        $updatedStock = Stock::findOrFail($stock->id);
        $this->assertEquals($stock->quantity + 1, $updatedStock->quantity);
    }
}
