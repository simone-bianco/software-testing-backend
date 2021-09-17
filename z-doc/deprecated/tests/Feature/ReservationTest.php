<?php

namespace Tests\Feature;

use App\Helper\CreateTestData;
use App\Models\Patient;
use App\Models\Reservation;
use App\Models\Stock;
use Artisan;
use Database\Factories\ReservationFactory;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Str;
use Tests\TestCase;
use Throwable;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    protected CreateTestData $createTestData;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->createTestData = $this->app->make(CreateTestData::class);
        Artisan::call('migrate:refresh');
    }


    /**
     * @throws Throwable
     */
    public function testCreateReservation() {
        $this->createTestData->patientsCreate();
        $this->createTestData->stocksCreate(1);
        $patient=Patient::firstOrFail();
        $stock=Stock::firstOrFail();

        ReservationFactory::new()->make(['code'=>Str::random(32),'state'=>'pending','stock_id'=>$stock->id,'patient_id'=>$patient->id,'date'=>'2021-09-10','time'=>'12:00:00'])->save();
        $reservation=Reservation::where('date','=','2021-09-10')->firstOrFail();
        self::assertEquals(Reservation::PENDING_STATE, $reservation->state);
        self::assertEquals('2021-09-10',$reservation->date->format('Y-m-d'));


    }
}
