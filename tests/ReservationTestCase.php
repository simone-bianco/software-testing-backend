<?php

namespace Tests;

use App\Models\Batch;
use App\Models\Patient;
use App\Models\Responsible;
use App\Models\Stock;
use App\Models\Structure;
use App\Models\Vaccine;
use App\Repositories\ReservationRepository;
use Database\Seeders\Test\BatchSeeder;
use Database\Seeders\Test\PatientsSeeder;
use Database\Seeders\Test\ResponsibleSeeder;
use Database\Seeders\Test\StocksSeeder;
use Database\Seeders\Test\StructuresSeeder;
use Database\Seeders\VaccinesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Throwable;

abstract class ReservationTestCase extends TestCase
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
    public function setUp() : void {
        parent::setUp();
        $this->reservationRepository = app(ReservationRepository::class);
        $this->structuresSeeder = app(StructuresSeeder::class);
        $this->vaccinesSeeder = app(VaccinesSeeder::class);
        $this->batchSeeder = app(BatchSeeder::class);
        $this->stockSeeder = app(StocksSeeder::class);
        $this->responsibleSeeder = app(ResponsibleSeeder::class);
        $this->patientsSeeder = app(PatientsSeeder::class);
        $this->refreshDatabase();
        $this->reseedDatabase();
    }

    /**
     * @throws ValidationException
     * @throws Throwable
     */
    protected function reseedDatabase()
    {
        $this->structuresSeeder->run();
        $this->vaccinesSeeder->run();
        $this->batchSeeder->run();
        $this->stockSeeder->run();
        $this->responsibleSeeder->run();
        $this->patientsSeeder->run();
    }

    protected function assertPreConditions(): void
    {
        $this->assertCount(1, Structure::all());
        $this->assertCount(4, Vaccine::all());
        $this->assertCount(4, Batch::all());
        $this->assertEquals(Structure::all()->count() * Batch::all()->count(), Stock::all()->count());
        $this->assertCount(1, Responsible::all());
        $this->assertCount(1, Patient::all());
    }
}
