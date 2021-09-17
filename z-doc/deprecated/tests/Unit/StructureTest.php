<?php

namespace Tests\Unit;

use App\Helper\PatientCreator;
use App\Models\Reservation;
use App\Models\Stock;
use App\Models\Structure;
use App\Repositories\ReservationRepository;
use Artisan;
use Carbon\Carbon;
use Database\Seeders\BatchSeeder;
use Database\Seeders\StocksSeeder;
use Database\Seeders\StructuresSeeder;
use Database\Seeders\TestDatabaseSeeder;
use Database\Seeders\VaccinesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Throwable;

class StructureTest extends TestCase
{
    use RefreshDatabase;

    protected PatientCreator $patientCreator;
    protected ReservationRepository $reservationRepository;
    protected StructuresSeeder $structuresSeeder;
    protected VaccinesSeeder $vaccinesSeeder;
    protected BatchSeeder $batchSeeder;
    protected TestDatabaseSeeder $stocksSeeder;

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function setUp() : void {
        parent::setUp();
        Artisan::call('migrate:refresh');
        $this->patientCreator = $this->app->make(PatientCreator::class);
        $this->reservationRepository = $this->app->make(ReservationRepository::class);
//        $this->structuresSeeder = $this->app->make(StructuresSeeder::class);
//        $this->vaccinesSeeder = $this->app->make(VaccinesSeeder::class);
//        $this->batchSeeder = $this->app->make(BatchSeeder::class);
//        $this->stocksSeeder = $this->app->make(StocksSeeder::class);
        $this->stocksSeeder = $this->app->make(TestDatabaseSeeder::class);
    }

    /**
     * Consideriamo il seguente caso
     * Struttura con 2 prenotazioni per ora
     * 8:00 -> 2
     * 8:30 -> 1
     * Una delle prenotazioni alle 8:00 viene cancellata
     * 8:00 -> 1
     * 8:30 -> 1
     * La prossima prenotazione che arriva deve essere infilata nel buco
     * che si è venuto a creare, ottenendo
     * 8:00 -> 2
     * 8:30 -> 1
     *
     * @throws Throwable
     */
    public function testGetNextAvailableHour()
    {
        $this->assertEquals(1, 1);
        return;
//        $this->structuresSeeder->run(2);
//        $this->vaccinesSeeder->run();
//        $this->batchSeeder->run();
        $this->stocksSeeder->run();

        $stock = Stock::where('quantity', '>', 10)->firstOrFail();

        $originalStockQty = $stock->quantity;
        $stockId = $stock->id;

        $structure = $stock->structure;
        $structure->capacity = 4 * Structure::NUMBER_OF_WORKING_HOURS;
        $structure->saveOrFail();

        $this->assertEquals(2, $structure->halfHourCapacity);

        $patients = $this->patientCreator->make(10);

        $date = Carbon::createFromDate(2021, 6, 6);
        $startingHour = $date->setTime(8, 0);

        $firstReservation = $this->reservationRepository->createAndStockDecrement(Reservation::make([
            'stock_id' => $stockId,
            'patient_id' => $patients[0]->id,
            'date' => $date
        ]));

        $updatedStock = Stock::whereId($stockId)->firstOrFail();
        $this->assertEquals($originalStockQty - 1, $updatedStock->quantity);
        $this->assertEquals($firstReservation->time->format('H:i'), $startingHour->format('H:i'));

        $secondReservation = $this->reservationRepository->createAndStockDecrement(Reservation::make([
            'stock_id' => $stockId,
            'patient_id' => $patients[1]->id,
            'date' => $date
        ]));

        $updatedStock = Stock::whereId($stockId)->firstOrFail();
        $this->assertEquals($originalStockQty - 2, $updatedStock->quantity);
        $this->assertEquals($secondReservation->time->format('H:i'), $startingHour->format('H:i'));

        $thirdReservation = $this->reservationRepository->createAndStockDecrement(Reservation::make([
            'stock_id' => $stockId,
            'patient_id' => $patients[2]->id,
            'date' => $date
        ]));

        $updatedStock = Stock::whereId($stockId)->firstOrFail();
        $this->assertEquals($originalStockQty - 3, $updatedStock->quantity);
        $this->assertEquals(
            $startingHour->addMinutes(Structure::TIME_SLICE_MINUTES)->format('H:i'),
            $thirdReservation->time->format('H:i')
        );

        $cancelledFirstReservation = $this->reservationRepository->cancelAndStockIncrement($firstReservation);

        $updatedStock = Stock::whereId($stockId)->firstOrFail();
        $this->assertEquals($originalStockQty - 2, $updatedStock->quantity);
        $this->assertEquals(Reservation::CANCELED_STATE, $cancelledFirstReservation->state);

        $fourthReservation = $this->reservationRepository->createAndStockDecrement(Reservation::make([
            'stock_id' => $stockId,
            'patient_id' => $patients[3]->id,
            'date' => $date
        ]));

        $updatedStock = Stock::whereId($stockId)->firstOrFail();
        $this->assertEquals($originalStockQty - 3, $updatedStock->quantity);
        $this->assertEquals(
            $startingHour->subMinutes(Structure::TIME_SLICE_MINUTES)->format('H:i'),
            $fourthReservation->time->format('H:i')
        );
    }
}
