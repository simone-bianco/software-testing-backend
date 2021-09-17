<?php


namespace App\Helper;


use App\Models\Batch;
use App\Models\Patient;
use App\Models\Stock;
use App\Models\Structure;
use App\Models\Vaccine;
use Database\Factories\BatchFactory;
use Database\Factories\ReservationFactory;
use Database\Factories\StockFactory;
use Database\Factories\StructureFactory;
use Database\Factories\VaccineFactory;
use Database\Seeders\PatientsSeeder;
use Exception;
use Illuminate;
use Str;
use Throwable;

class CreateTestData
{
    protected PatientsSeeder $patientsSeeder;

    /**
     * DatabaseSeeder constructor.
     * @param  PatientsSeeder  $patientsSeeder
     */
    public function __construct(PatientsSeeder $patientsSeeder) {
        $this->patientsSeeder = $patientsSeeder;

    }

    /**
     * @throws Exception
     */
    public function structuresCreate($number): void
    {
        Structure::factory()->count($number)->create();

    }

    public function vaccinesCreate(): void {
        VaccineFactory::new()->make(['name'=>'AstraZeneca','vaccine_doses'=>2])->save();
        VaccineFactory::new()->make(['name'=>'J&J','vaccine_doses'=>1])->save();
        VaccineFactory::new()->make(['name'=>'Moderna','vaccine_doses'=>2])->save();
        VaccineFactory::new()->make(['name'=>'Pfizer','vaccine_doses'=>2])->save();

    }

    /**
     * @throws Exception
     */
    public function batchesCreate($number): void {  //number specifica il numero di batch per ogni vaccinio da creare
        $this->vaccinesCreate();
        $vaccines=Vaccine::get();
        foreach ($vaccines as $vaccine) {
            for ($i = 0; $i < $number; $i++) {
                BatchFactory::new()->make(['vaccine_id' => $vaccine->id, 'code' =>uniqid("SERIAL")])->save();
            }
        }

    }

    /**
     * @throws Exception
     */
    public function stocksCreate($number): void {   //number rappresenta il numero di stock per ogni struttura da creare
        $this->batchesCreate(10);
        $this->structuresCreate(10);
        $batches=Batch::get();
        $structures=Structure::get();

        $batchSize=count($batches);

        foreach ($structures as $structure) {
            for ($i=0;$i<$number;$i++) {
                $randomBatch = random_int(1, $batchSize - 1);
                StockFactory::new()->make(['structure_id' => $structure->id, 'batch_id' => $batches[$randomBatch]->id, 'quantity' => random_int(1, 100000)])->save();
            }
        }
    }

    /**
     * @throws Throwable
     */
    public function patientsCreate(): void {
       $this->patientsSeeder->run();
    }

    /**
     * @throws Throwable
     */
    public function reservationsCreate($number): void { //number*10 rappresenta il numero totale di prenotazioni (una per ogni stock creato)
        $this->patientsCreate();
        $this->stocksCreate($number);
        $patients=Patient::get();
        $stocks=Stock::get();
        $sizePatients=count($patients);

        foreach ($stocks as $stock) {
            $randomDay=mt_rand(1,28);
            $randomMonth=mt_rand(1,12);
            $randomPatient=random_int(1,$sizePatients-1);

            ReservationFactory::new()->make(['code'=>Str::random(32),'patient_id'=>$patients[$randomPatient]->id,'stock_id'=>$stock->id, 'date'=>'2021-'.$randomMonth.'-'.$randomDay,'state'=>'confirmed','time'=>time()])->save();
        }
    }

}
