<?php

namespace Tests\Unit;

use App\Helper\CreateTestData;
use App\Models\Patient;
use App\Models\Reservation;
use Artisan;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    protected CreateTestData $createTestData;

    /**
     * @throws BindingResolutionException
     */
    public function setUp() : void {
        parent::setUp();
        $this->createTestData = $this->app->make(CreateTestData::class);
        Artisan::call('migrate:refresh');
    }

    /**
     * @throws \Throwable
     */
    public function testHasBooking()
    {
        //crea delle prenotazioni
        $this->createTestData->reservationsCreate(20);
        $patients=Patient::get();
        $reservations=Reservation::get();

        //associo a paziente1 una prenotazione
        $patient1=$patients[0];


        //tolgo eventuali prenotazioni da paziente2 e le metto a paziente1
        $patient2=$patients[2];
        $reservations2=Reservation::where('patient_id','=',$patient2->id)->get();
        foreach ($reservations2 as $res) {
            $res->patient_id=$patient1->id;
            $res->save();
        }

        self::assertEquals(true,$patient1->hasBooking());
        self::assertEquals(false,$patient2->hasBooking());

    }


}
