<?php

namespace Tests\Feature;

use App\Helper\PatientCreator;
use App\Models\Batch;
use App\Models\Patient;
use App\Models\Reservation;
use App\Models\Responsible;
use App\Models\Stock;
use App\Models\Structure;
use App\Models\Vaccine;
use App\Repositories\ReservationRepository;
use Carbon\Carbon;
use Database\Seeders\Test\BatchSeeder;
use Database\Seeders\Test\PatientsSeeder;
use Database\Seeders\Test\ResponsibleSeeder;
use Database\Seeders\Test\StocksSeeder;
use Database\Seeders\Test\StructuresSeeder;
use Database\Seeders\VaccinesSeeder;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Session;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Throwable;

class ResponsibleHandleReservationTest extends TestCase
{
    use RefreshDatabase;

    protected ReservationRepository $reservationRepository;
    protected $structuresSeeder;
    protected $vaccinesSeeder;
    protected $batchSeeder;
    protected $responsibleSeeder;
    protected $stockSeeder;
    protected $patientsSeeder;

    /**
     * @throws Throwable
     * @throws ValidationException
     */
    protected function reseedDatabase()
    {
        $this->refreshDatabase();
        $this->structuresSeeder->run(1);
        $this->assertCount(1, Structure::all());
        $this->vaccinesSeeder->run();
        $this->assertCount(4, Vaccine::all());
        $this->batchSeeder->run(1);
        $this->assertCount(4, Batch::all());
        $this->stockSeeder->run(1);
        $this->assertEquals(Structure::all()->count() * Batch::all()->count(), Stock::all()->count());
        $this->responsibleSeeder->run(true);
        $this->assertCount(1, Responsible::all());
        $this->patientsSeeder->run(1);
        $this->assertCount(1, Patient::all());
    }

    /**
     * @throws BindingResolutionException
     * @throws Throwable
     */
    public function setUp() : void {
        parent::setUp();
        $this->reservationRepository = $this->app->make(ReservationRepository::class);
        $this->structuresSeeder = $this->app->make(StructuresSeeder::class);
        $this->vaccinesSeeder = $this->app->make(VaccinesSeeder::class);
        $this->batchSeeder = $this->app->make(BatchSeeder::class);
        $this->responsibleSeeder = $this->app->make(ResponsibleSeeder::class);
        $this->stockSeeder = $this->app->make(StocksSeeder::class);
        $this->patientsSeeder = $this->app->make(PatientsSeeder::class);
    }

    /**
     * @throws ValidationException
     * @throws Throwable
     */
    public function testAcceptReservation()
    {
        $this->reseedDatabase();

        $structure = Structure::first();
        $this->assertNotNull($structure);

        /** @var Responsible $responsible */
        $responsible = $structure->responsibles()->first();
        $this->assertNotNull($responsible);

        $patient = Patient::first();
        $this->assertNotNull($patient);

        $reservation = $this->createReservation($structure, $patient);
        $this->assertNotNull($reservation);
        $stock = $reservation->stock;
        $this->assertNotNull($reservation);

        $this->be($responsible->account->user);
        Session::put('2fa', true);
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
     * @throws ValidationException
     * @throws Throwable
     */
    public function testAcceptReservationAndChangeVaccine()
    {
        $this->reseedDatabase();

        $structure = Structure::first();
        $this->assertNotNull($structure);

        /** @var Responsible $responsible */
        $responsible = $structure->responsibles()->first();
        $this->assertNotNull($responsible);

        $patient = Patient::first();
        $this->assertNotNull($patient);

        $reservation = $this->createReservation($structure, $patient);
        $this->assertNotNull($reservation);
        $stock = $reservation->stock;
        $this->assertNotNull($stock);

        $anotherVaccine = Vaccine::where('id', '!=', $stock->batch->vaccine->id)->first();
        $this->assertNotNull($anotherVaccine);

        /** @var Stock $differentAvailableStock */
        $differentAvailableStock = $structure->getMaxStock([$anotherVaccine]);
        $this->assertNotNull($differentAvailableStock);
        $differentAvailableStockQty = $differentAvailableStock->quantity;
        $this->assertGreaterThanOrEqual(1, $differentAvailableStockQty);

        $user = $responsible->account->user;
        $this->assertNotNull($user);
        $this->be($responsible->account->user);
        Session::put('2fa', true);
        $this->assertAuthenticatedAs($user);
        $response = $this->call(
            'PUT',
            route('reservations.update', $reservation->id),
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
     * @throws ValidationException
     * @throws Throwable
     */
    public function testCompleteReservationAndCreateRecall()
    {
        $this->reseedDatabase();

        $structure = Structure::firstOrFail();

        /** @var Responsible $responsible */
        $responsible = $structure->responsibles()->firstOrFail();

        $patient = Patient::first();
        $this->assertNotNull($patient);

        $newReservation = $this->createReservation($structure, $patient);
        $reservation = $this->reservationRepository->confirmAndSave($newReservation);

        $this->be($responsible->account->user);
        Session::put('2fa', true);
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
     * @throws ValidationException
     * @throws Throwable
     */
    public function testRefuseReservation()
    {
        $this->reseedDatabase();

        $structure = Structure::firstOrFail();

        /** @var Responsible $responsible */
        $responsible = $structure->responsibles()->first();
        $this->assertNotNull($responsible);

        $patient = Patient::first();
        $this->assertNotNull($patient);

        $reservation = $this->createReservation($structure, $patient);

        $this->be($responsible->account->user);
        Session::put('2fa', true);
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
     * @param  Patient  $patient
     * @return Reservation
     * @throws Throwable
     * @throws ValidationException
     */
    protected function createReservation(Structure $structure, Patient $patient): Reservation
    {
        /** @var Stock $availableStock */
        $availableStock = $structure->stocks()->where('quantity', '>', 0)->first();
        $this->assertNotNull($availableStock);

        return $this->reservationRepository->createAndStockDecrement(
            Reservation::factory()->make([
                'date' => Carbon::now()->addDay(),
                'patient_id' => $patient->id,
                'stock_id' => $availableStock->id
            ])
        );
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
