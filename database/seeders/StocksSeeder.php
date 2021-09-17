<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Structure;
use App\Repositories\StockRepository;
use Database\Factories\StockFactory;
use Illuminate\Database\Seeder;
use Illuminate\Validation\ValidationException;
use Log;

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
    
    public function run()
    {
        $structures = Structure::all();
        $batches = Batch::all();

        foreach ($structures as $structure) {
            foreach ($batches as $batch) {
                try {
                    $this->stockRepository->create(
                        StockFactory::new()->make([
                            "structure_id" => $structure->id,
                            "quantity" => rand(0, 150),
                            "batch_id" => $batch->id
                        ]));
                } catch (ValidationException $e) {
                    Log::error(sprintf("StockSeeder error: %s", $e->getMessage()));
                }
            }
        }
    }
}
