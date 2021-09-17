<?php

namespace Tests\Feature;

use App\Helper\CreateTestData;
use App\Models\Patient;
use App\Models\Stock;
use App\Repositories\StructureRepository;
use Artisan;
use Database\Factories\ReservationFactory;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Str;
use Tests\TestCase;
use Throwable;

class getBusyDatesTest extends TestCase
{
    use RefreshDatabase;

    protected CreateTestData $createTestData;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:refresh');
        $this->createTestData = $this->app->make(CreateTestData::class);
    }

        /**
         * @throws Throwable
         */
        public function testGetBusyDates()
        {
            //creo stock e pazienti
            $this->createTestData->stocksCreate(50);
            $this->createTestData->patientsCreate();
            $stocks=Stock::get();   //stock list
            $patients=Patient::get();
            //utilizzo come struttura di test quella collegata al primo stock della lista
            $structure=$stocks[0]->structure;
            //imposto una capacity bassa per structure
            $structure->capacity=10;
            $structure->save();

            //per ogni paziente creo una nuova prenotazione con data 20-07-2021, relative allo stock[0]
            foreach ($patients as $patient) {
                ReservationFactory::new()->make(['code'=>Str::random(32),'date'=>'2021-07-20','time'=>'11:17','state'=>'pending','patient_id'=>$patient->id, 'stock_id'=>$stocks[0]->id])->save();
            }

            for($i=0;$i<9;$i++) {
                ReservationFactory::new()->make(['code'=>Str::random(32),'date'=>'2021-05-10','time'=>'11:17','state'=>'pending','patient_id'=>$patients[$i]->id, 'stock_id'=>$stocks[0]->id])->save();
            }
            foreach ($patients as $patient) {
                ReservationFactory::new()->make(['code'=>Str::random(32),'date'=>'2021-10-30','time'=>'11:17','state'=>'pending','patient_id'=>$patient->id, 'stock_id'=>$stocks[0]->id])->save();
            }



            $dates=$structure->getBusyDates();
            self::assertCount(1, $dates);
            self::assertContains('2021-10-30',$dates,'Data 2021-10-30 non presente');
            self::assertNotContains('2021-05-10',$dates,'Data 2021-05-10 presente');

        }


}
