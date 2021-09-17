<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Log;
use Throwable;

class DatabaseSeeder extends Seeder
{
    protected PatientsSeeder $patientsSeeder;
    protected VaccinesSeeder $vaccineSeeder;
    protected StructuresSeeder $structuresSeeder;
    protected StocksSeeder $stocksSeeder;
    protected BatchSeeder $batchSeeder;
    protected AccountSeeder $accountSeeder;
    protected ResponsibleSeeder $responsibleSeeder;
    protected ReservationsSeeder $reservationsSeeder;

    /**
     * DatabaseSeeder constructor.
     * @param  AccountSeeder  $accountSeeder
     * @param  PatientsSeeder  $patientsSeeder
     * @param  VaccinesSeeder  $vaccineSeeder
     * @param  StructuresSeeder  $structuresSeeder
     * @param  StocksSeeder  $stocksSeeder
     * @param  BatchSeeder  $batchSeeder
     * @param  ResponsibleSeeder  $responsibleSeeder
     * @param  ReservationsSeeder  $reservationsSeeder
     */
    public function __construct(
        AccountSeeder $accountSeeder,
        PatientsSeeder $patientsSeeder,
        VaccinesSeeder $vaccineSeeder,
        StructuresSeeder $structuresSeeder,
        StocksSeeder $stocksSeeder,
        BatchSeeder $batchSeeder,
        ResponsibleSeeder $responsibleSeeder,
        ReservationsSeeder $reservationsSeeder
    ) {
        $this->accountSeeder = $accountSeeder;
        $this->patientsSeeder = $patientsSeeder;
        $this->vaccineSeeder = $vaccineSeeder;
        $this->structuresSeeder=$structuresSeeder;
        $this->stocksSeeder = $stocksSeeder;
        $this->batchSeeder = $batchSeeder;
        $this->responsibleSeeder = $responsibleSeeder;
        $this->reservationsSeeder = $reservationsSeeder;
    }

    /**
     * @throws Throwable
     */
    public function run()
    {
        try {
            $this->structuresSeeder->run(2);
            $this->patientsSeeder->run();
            $this->vaccineSeeder->run();
            $this->batchSeeder->run();
            $this->stocksSeeder->run();
            $this->responsibleSeeder->run();
            $this->reservationsSeeder->run();
            $this->accountSeeder->run();
            $this->reservationsSeeder->run();
        } catch (Throwable $e) {
            var_dump($e->getMessage());
            Log::error($e->getMessage());
        }
    }
}
