<?php

namespace Tests\Unit;

use App\Models\Batch;
use App\Models\Vaccine;
use Artisan;
use Database\Factories\BatchFactory;
use Database\Factories\VaccineFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BatchTest extends TestCase
{
    use RefreshDatabase;

    public function setUp() : void {
        parent::setUp();
        Artisan::call('migrate:refresh');
    }

    public function testCreateBatch()
    {
        VaccineFactory::new()->create(['name'=>'astrazeneca','vaccine_doses'=>2]);

        $vaccine=Vaccine::firstOrFail();
        BatchFactory::new()->make(['code' => '123456789','vaccine_id' => $vaccine->id])->save();

        $batch=Batch::firstOrFail();

        self::assertEquals('123456789',$batch->code);

    }
}
