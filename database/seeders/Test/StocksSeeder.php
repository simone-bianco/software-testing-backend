<?php

namespace Database\Seeders\Test;

use App\Models\Batch;
use App\Models\Structure;
use App\Repositories\StockRepository;
use Database\Factories\StockFactory;
use Illuminate\Database\Seeder;
use Illuminate\Validation\ValidationException;
use Log;
use Throwable;

class StocksSeeder extends Seeder
{
    protected Structure $structure;
    protected Batch $batch;
    protected StockRepository $stockRepository;

    /**
     * StocksSeeder constructor.
     * @param  StockRepository  $stockRepository
     * @param  Structure  $structure
     * @param  Batch  $batch
     */
    public function __construct(
        StockRepository $stockRepository,
        Structure $structure,
        Batch $batch
    ) {
        $this->stockRepository = $stockRepository;
        $this->structure = $structure;
        $this->batch = $batch;
    }

    /**
     * @throws Throwable
     */
    public function run(int $defaultQty = 0)
    {
        $structures = Structure::all();
        $batches = Batch::all();

        foreach ($structures as $structure) {
            foreach ($batches as $batch) {
                try {
                    StockFactory::new()->make([
                        "structure_id" => $structure->id,
                        "quantity" => $defaultQty,
                        "batch_id" => $batch->id
                    ])->saveOrFail();
                } catch (ValidationException $e) {
                    Log::error(sprintf("StockSeeder error: %s", $e->getMessage()));
                }
            }
        }
    }
}
