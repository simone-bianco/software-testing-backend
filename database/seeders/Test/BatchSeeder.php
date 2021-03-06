<?php

namespace Database\Seeders\Test;

use App\Models\Vaccine;
use Database\Factories\VaccineFactory;
use Illuminate\Database\Seeder;
use Throwable;

class BatchSeeder extends Seeder
{
    /**
     * @throws Throwable
     */
    public function run(int $numberOfBatches = 3)
    {
        $vaccines = Vaccine::all();
        foreach ($vaccines as $vaccine){
            for ($i = 0; $i < $numberOfBatches; $i++) {
                $vaccine->batches()->make([
                    "code" => sprintf('%s%s%s', $vaccine->name, '_batch_', $i),
                ])->saveOrFail();
            }
        }
    }
}
