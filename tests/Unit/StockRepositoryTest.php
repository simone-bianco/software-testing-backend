<?php

namespace Tests\Unit;

use App\Models\Batch;
use App\Models\Stock;
use App\Models\Structure;
use App\Repositories\StockRepository;
use App\Validators\StockValidator;
use Illuminate\Support\Str;
use Mockery;
use Tests\BaseTestCase;

class StockRepositoryTest extends BaseTestCase
{
    public function testUnitCreateStock()
    {
        //creazione mock di StockValidator
        $this->instance(
            StockValidator::class,
            Mockery::mock(StockValidator::class, function (Mockery\MockInterface $mock) {
                $mock->shouldReceive('validateData')
                    ->once()
                    ->withArgs(fn($newStock, $extendParams) => true);
            })
        );

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

    public function testUnitDecrementAndSaveStock()
    {
        //creazione mock di StockValidator
        $this->instance(
            StockValidator::class,
            Mockery::mock(StockValidator::class, function (Mockery\MockInterface $mock) {
                $mock->shouldReceive('validateData')
                    ->once()
                    ->withArgs(fn($newStock, $extendParams) => true);
            })
        );

        $stock = $this->createTestStock([
            'code' => Str::random(32)
        ]);
        $qty = $stock->quantity;
        $this->assertNotNull($stock->id);

        $stockRepository = app(StockRepository::class);

        $incrementedStock = $stockRepository->incrementAndSave($stock);
        $this->assertEquals($qty + 1, $incrementedStock->quantity);
    }

    public function testUnitIncrementAndSaveStock()
    {
        //creazione mock di StockValidator
        $this->instance(
            StockValidator::class,
            Mockery::mock(StockValidator::class, function (Mockery\MockInterface $mock) {
                $mock->shouldReceive('validateData')
                    ->once()
                    ->withArgs(fn($newStock, $extendParams) => true);
            })
        );

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
        return Stock::make($this->getTestStockData());
    }

    protected function createTestStock(array $override = []): Stock
    {
        return Stock::create(array_merge($this->getTestStockData(), $override));
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
