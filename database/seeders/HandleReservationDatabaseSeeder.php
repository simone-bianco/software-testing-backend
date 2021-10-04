<?php

namespace Database\Seeders;

use Database\Seeders\Test\BatchSeeder as BatchSeeder;
use Database\Seeders\Test\ResponsibleSeeder as ResponsibleSeeder;
use Database\Seeders\Test\StocksSeeder as StocksSeeder;
use Database\Seeders\Test\StructuresSeeder as StructuresSeeder;
use Database\Seeders\Test\PatientsSeeder as PatientsSeeder;
use Illuminate\Database\Seeder;
use Throwable;

class HandleReservationDatabaseSeeder extends Seeder
{
    protected VaccinesSeeder $vaccineSeeder;
    protected StructuresSeeder $structuresSeeder;
    protected StocksSeeder $stocksSeeder;
    protected BatchSeeder $batchSeeder;
    protected ResponsibleSeeder $responsibleSeeder;
    protected PatientsSeeder $patientsSeeder;

    /**
     * DatabaseSeeder constructor.
     * @param  VaccinesSeeder  $vaccineSeeder
     * @param  StructuresSeeder  $structuresSeeder
     * @param  StocksSeeder  $stocksSeeder
     * @param  BatchSeeder  $batchSeeder
     * @param  ResponsibleSeeder  $responsibleSeeder
     * @param  PatientsSeeder  $patientsSeeder
     */
    public function __construct(
        VaccinesSeeder $vaccineSeeder,
        StructuresSeeder $structuresSeeder,
        StocksSeeder $stocksSeeder,
        BatchSeeder $batchSeeder,
        ResponsibleSeeder $responsibleSeeder,
        PatientsSeeder $patientsSeeder
    ) {
        $this->vaccineSeeder = $vaccineSeeder;
        $this->structuresSeeder=$structuresSeeder;
        $this->stocksSeeder = $stocksSeeder;
        $this->batchSeeder = $batchSeeder;
        $this->responsibleSeeder = $responsibleSeeder;
        $this->patientsSeeder = $patientsSeeder;
    }

    /**
     * @throws Throwable
     */
    public function run()
    {
        $this->structuresSeeder->run(1);
        $this->vaccineSeeder->run();
        $this->batchSeeder->run(1);
        $this->stocksSeeder->run(2);
        $this->responsibleSeeder->run();
        $this->patientsSeeder->run(1);
    }
}
