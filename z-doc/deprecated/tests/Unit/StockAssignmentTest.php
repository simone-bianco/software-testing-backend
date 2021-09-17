<?php

namespace Tests\Unit;

use App\Models\Stock;
use App\Models\Structure;
use App\Models\Vaccine;
use App\Repositories\BatchRepository;
use App\Repositories\StructureRepository;
use App\Repositories\VaccineRepository;
use Arr;
use Artisan;
use Database\Factories\BatchFactory;
use Database\Factories\StructureFactory;
use Database\Factories\VaccineFactory;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Throwable;

class StockAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected StructureRepository $structureRepository;
    protected VaccineRepository $vaccineRepository;
    protected BatchRepository $batchRepository;

    /**
     * @throws BindingResolutionException
     */
    public function setUp() : void {
        parent::setUp();
        $this->structureRepository = $this->app->make(StructureRepository::class);
        $this->vaccineRepository = $this->app->make(VaccineRepository::class);
        $this->batchRepository = $this->app->make(BatchRepository::class);
        Artisan::call('migrate:refresh');
    }

    /**
     * @throws Throwable
     */
    public function testGetMaxStock()
    {
        $structure = $this->structureRepository->saveOrCreate(
            StructureFactory::new()->make(['name' => 'test_structure'])
        );

        $firstVaccine = $this->vaccineRepository->saveOrCreate(
            VaccineFactory::new()->make(['name' => 'test_vaccine_1', 'vaccine_doses' => 1])
        );
        $secondVaccine = $this->vaccineRepository->saveOrCreate(
            VaccineFactory::new()->make(['name' => 'test_vaccine_2', 'vaccine_doses' => 1])
        );
        $notIncludedVaccine = $this->vaccineRepository->saveOrCreate(
            VaccineFactory::new()->make(['name' => 'test_vaccine_not_included', 'vaccine_doses' => 1])
        );

        $firstVaccineStocks = $this->createStocks($firstVaccine, $structure);
        $secondVaccineStocks = $this->createStocks($secondVaccine, $structure);
        $notIncludedVaccineStocks = $this->createStocks($notIncludedVaccine, $structure);
        $notIncludedVaccineStocks[0]->quantity = 5000;
        $notIncludedVaccineStocks[0]->save();

        $this->assertCount(5, $firstVaccineStocks);
        $this->assertCount(5, $secondVaccineStocks);
        $this->assertCount(5, $notIncludedVaccineStocks);

        $expectedMaxStock = Arr::first($firstVaccineStocks);
        $expectedMaxStock->quantity = 1000;
        $expectedMaxStock->save();

        $maxStock = $structure->getMaxStock([$firstVaccine, $secondVaccine]);
        $this->assertEquals($expectedMaxStock->id, $maxStock->id);

        $expectedMaxStock = Arr::last($secondVaccineStocks);
        $expectedMaxStock->quantity = 10000;
        $expectedMaxStock->save();

        $maxStock = $structure->getMaxStock([$firstVaccine, $secondVaccine]);

        $this->assertEquals($expectedMaxStock->id, $maxStock->id);
    }

    /**
     * @param  Vaccine  $vaccine
     * @param  Structure  $structure
     * @return Stock[]
     * @throws ValidationException
     */
    protected function createStocks(Vaccine $vaccine, Structure $structure): array
    {
        /** @var Stock[] $stocks */
        $stocks = [];

        for ($i = 0; $i < 5; $i++) {
            $batch = $this->batchRepository->saveOrCreate(BatchFactory::new()->make(), $vaccine);
            $stocks[] = $structure->stocks()->create(['batch_id' => $batch->id, 'quantity' => 30 * $i, 'code' => \Str::random(8)]);
        }

        return $stocks;
    }
}
