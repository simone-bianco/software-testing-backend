<?php

namespace Database\Seeders;

use Database\Seeders\Test\BatchSeeder as BatchSeeder;
use Database\Seeders\Test\ResponsibleSeeder as ResponsibleSeeder;
use Database\Seeders\Test\StocksSeeder as StocksSeeder;
use Database\Seeders\Test\StructuresSeeder as StructuresSeeder;
use Illuminate\Database\Seeder;
use Throwable;

class TestDatabaseSeeder extends Seeder
{
    protected VaccinesSeeder $vaccineSeeder;
    protected StructuresSeeder $structuresSeeder;
    protected StocksSeeder $stocksSeeder;
    protected BatchSeeder $batchSeeder;
    protected ResponsibleSeeder $responsibleSeeder;

    /**
     * DatabaseSeeder constructor.
     * @param  VaccinesSeeder  $vaccineSeeder
     * @param  StructuresSeeder  $structuresSeeder
     * @param  StocksSeeder  $stocksSeeder
     * @param  BatchSeeder  $batchSeeder
     * @param  ResponsibleSeeder  $responsibleSeeder
     */
    public function __construct(
        VaccinesSeeder $vaccineSeeder,
        StructuresSeeder $structuresSeeder,
        StocksSeeder $stocksSeeder,
        BatchSeeder $batchSeeder,
        ResponsibleSeeder $responsibleSeeder
    ) {
        $this->vaccineSeeder = $vaccineSeeder;
        $this->structuresSeeder=$structuresSeeder;
        $this->stocksSeeder = $stocksSeeder;
        $this->batchSeeder = $batchSeeder;
        $this->responsibleSeeder = $responsibleSeeder;
    }

    /**
     * @throws Throwable
     */
    public function run()
    {
        $this->structuresSeeder->run();
        $this->vaccineSeeder->run();
        $this->batchSeeder->run(2);
        $this->stocksSeeder->run();
        $this->responsibleSeeder->run();
    }
}
