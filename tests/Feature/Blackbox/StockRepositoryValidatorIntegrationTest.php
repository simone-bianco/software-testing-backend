<?php

namespace Tests\Feature\Blackbox;

use App\Models\Batch;
use App\Models\Stock;
use App\Models\Structure;
use App\Repositories\StockRepository;
use Database\Factories\StockFactory;
use Illuminate\Support\Str;
use Tests\BaseTestCase;

/**
 * @group reservationIntegration
 */
class StockRepositoryValidatorIntegrationTest extends BaseTestCase
{
    public function testIntegrationValidatorCreateStock()
    {
        $stock = $this->getTestStock();

        $stockRepository = app(StockRepository::class);

        $createdStock = $stockRepository->create($stock);
        $this->assertInstanceOf(Stock::class, $createdStock);
        $this->assertNotNull($createdStock->id);
        $this->assertNotNull($createdStock->code);
        $this->assertEquals($stock->quantity, $createdStock->quantity);
        $this->assertEquals($stock->batch_id, $createdStock->batch_id);
        $this->assertEquals($stock->structure_id, $createdStock->structure_id);
    }

    public function testIntegrationValidatorDecrementAndSaveStock()
    {
        $stock = $this->createTestStock([
            'code' => Str::random(32)
        ]);
        $qty = $stock->quantity;
        $this->assertNotNull($stock->id);

        $stockRepository = app(StockRepository::class);

        $incrementedStock = $stockRepository->incrementAndSave($stock);
        $this->assertEquals($qty + 1, $incrementedStock->quantity);
    }

    public function testIntegrationValidatorIncrementAndSaveStock()
    {
        $stock = $this->createTestStock([
            'code' => Str::random(32)
        ]);
        $qty = $stock->quantity;
        $this->assertNotNull($stock->id);

        $stockRepository = app(StockRepository::class);

        $incrementedStock = $stockRepository->decrementAndSave($stock);
        $this->assertEquals($qty - 1, $incrementedStock->quantity);
    }

    protected function getTestStock(): Stock
    {
        return StockFactory::new()->make($this->getTestStockData());
    }

    protected function createTestStock(array $override = []): Stock
    {
        return StockFactory::new()->create(array_merge($this->getTestStockData(), $override));
    }

    protected function getTestStockData(): array
    {
        return [
            'structure_id' => Structure::firstOrFail()->id,
            'quantity' => 5,
            'batch_id' => Batch::firstOrFail()->id
        ];
    }
}
