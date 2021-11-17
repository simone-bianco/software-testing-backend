<?php

namespace Tests;

use App\Models\Batch;
use App\Models\Patient;
use App\Models\Reservation;
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
        //Eseguo i seeder
        $this->structuresSeeder->run();
        $this->vaccinesSeeder->run();
        $this->batchSeeder->run();
        $this->stockSeeder->run();
        $this->responsibleSeeder->run();
        $this->patientsSeeder->run();
    }

    protected function assertPreConditions(): void
    {
        //Mi assicuro che la quantità di dati inserita corrisponda a quella aspettata
        $this->assertDatabaseCount('structures', 1);
        $this->assertDatabaseCount('vaccines', 4);
        $this->assertDatabaseCount('batches', Vaccine::all()->count());
        $this->assertDatabaseCount('stocks', Structure::all()->count() * Batch::all()->count());
        $this->assertDatabaseCount('responsibles', Structure::all()->count());
        $this->assertDatabaseCount('patients', Structure::all()->count());
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


    private function sortByLength(array &$array)
    {
        array_multisort(array_map('count', $array), SORT_DESC, $array);
    }

    private function uniqueCombination($values, $minLength = 1, $maxLength = 2000): array
    {
        $count = count($values);
        $size = pow(2, $count);
        $keys = array_keys($values);
        $return = [];

        for($i = 0; $i < $size; $i ++) {
            $b = sprintf("%0" . $count . "b", $i);
            $out = [];

            for($j = 0; $j < $count; $j ++) {
                if ($b[$j] == '1') {
                    $out[$keys[$j]] = $values[$keys[$j]];
                }
            }

            if (count($out) >= $minLength && count($out) <= $maxLength) {
                $return[] = $out;
            }
        }

        return $return;
    }

    function getArrayCombinations($arrays, bool $uniqueKeys = false): array
    {
        $result = array(array());
        foreach ($arrays as $property => $property_values) {
            $tmp = array();
            foreach ($result as $result_item) {
                foreach ($property_values as $property_key => $property_value) {
                    $tmp[] = $result_item + array("$property.$property_key" => $property_value);
                }
            }
            if (!$uniqueKeys) {
                $result = array_values($tmp);
            }
        }
        return $result;
    }

    protected function generateKCombinations($values, $k): array
    {
        # per prima cosa ordino l'array in base al numero degli input
        $this->sortByLength($values);
        /*
        * parto dall'array contenente il dati da provare, es:
        *   $indexesByKeys = [
        *    'code' => [
        *       0 => 0, # sono gli indici del dizionario -> $dictionary['code'][0] = primo valore da provare di code
        *       1 => 1,
        *       etc...
        *     ]
        *   etc...
        *   ]
        */
        # Formo le k-uple, prendendo k campi alla volta
        $fieldCombinations = [];
        $fieldKeys = array_keys($values);
        $i = 0;
        $valuesLength = sizeof($values);
        while ($i < $valuesLength) {
            # prendo k campi, es. per k=2 $kFields = ['date', 'code']
            $kFields = array_slice($fieldKeys, $i, min($k, $valuesLength - $i));
            # genero chiave unique per la k-pla es. $key = 'date.code'
            $key = implode('.', $kFields);

            $inputCombinations = [];
            foreach ($kFields as $field) {
                $inputCombinations[$field] = array_keys($values[$field]);
            }
            $fieldCombinations[$key] = $inputCombinations;

            $fieldCombinations[$key] = $this->getArrayCombinations($fieldCombinations[$key]);

            $i += $k;
        }
        /*
         * Il risultato è un array contenente le coppie con la lista di valori da provare, in questo formato
         * $fieldCombinations = [
         *    "state.patient" => [
         *        0 => [
         *          "state.0" => 0
         *          "patient.0" => 0
         *        ]
         *        1 => [
         *          "state.0" => 0
         *          "patient.1" => 1
         *        ]
         *    etc..
         *    ],
         *    "code.time" => [...]
         *    etc...
         * ]
         */
        foreach ($fieldCombinations as $keyCombo => $values) {
            foreach ($values as $index => $value) {
                $fieldCombinations[$keyCombo][$index] = array_values($value);
            }
        }
        /*
         * Il risultato è un array contenente le coppie con la lista di valori da provare, in questo formato
         * $fieldCombinations = [
         *    "state.patient" => [
         *        0 => [
         *          0 => 0
         *          1 => 0
         *        ]
         *        1 => [
         *          0 => 0
         *          1 => 1
         *        ]
         *    etc..
         *    ],
         *    "code.time" => [...]
         *    etc...
         * ]
         */
        return $fieldCombinations;
    }
}
