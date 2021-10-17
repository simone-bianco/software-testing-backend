<?php

namespace Tests;

use App\Models\Batch;
use App\Models\Patient;
use App\Models\Reservation;
use App\Models\Stock;
use App\Models\Structure;
use App\Repositories\ReservationRepository;
use Carbon\Carbon;
use Database\Seeders\Test\BatchSeeder;
use Database\Seeders\Test\PatientsSeeder;
use Database\Seeders\Test\ResponsibleSeeder;
use Database\Seeders\Test\StocksSeeder;
use Database\Seeders\Test\StructuresSeeder;
use Database\Seeders\VaccinesSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Throwable;

abstract class BackofficeDuskTestCase extends DuskTestCase
{
    use DatabaseMigrations;

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
    public function setUp() : void {
        parent::setUp();
        $this->reservationRepository = app(ReservationRepository::class);
        $this->structuresSeeder = app(StructuresSeeder::class);
        $this->vaccinesSeeder = app(VaccinesSeeder::class);
        $this->batchSeeder = app(BatchSeeder::class);
        $this->stockSeeder = app(StocksSeeder::class);
        $this->responsibleSeeder = app(ResponsibleSeeder::class);
        $this->patientsSeeder = app(PatientsSeeder::class);
        $this->runDatabaseMigrations();
        $this->reseedDatabase();
    }

    /**
     * @throws ValidationException
     * @throws Throwable
     */
    protected function reseedDatabase()
    {
        //Eseguo i seeder
        $this->structuresSeeder->run();
        $this->vaccinesSeeder->run();
        $this->batchSeeder->run();
        $this->stockSeeder->run();
        $this->responsibleSeeder->run(false);
        $this->patientsSeeder->run();
    }

    protected function assertPreConditions(): void
    {
        //Mi assicuro che la quantità di dati inserita corrisponda a quella aspettata
        $this->assertDatabaseCount('structures', 1);
        $this->assertDatabaseCount('vaccines', 4);
        $this->assertDatabaseCount('batches', 4);
        $this->assertDatabaseCount('stocks', Structure::all()->count() * Batch::all()->count());
        $this->assertDatabaseCount('responsibles', 1);
        $this->assertDatabaseCount('patients', 1);
    }


    /**
     * Chiama semplicemente il repository, non simula la creazione di una reservation da parte di un paziente
     * (non chiama le API)
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
}
