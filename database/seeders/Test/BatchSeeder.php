<?php

namespace Database\Seeders\Test;

use App\Models\Vaccine;
use Illuminate\Database\Seeder;
use Throwable;

class BatchSeeder extends Seeder
{
    /**
     * @throws Throwable
     */
    public function run(int $numberOfBatchesPerVaccine = 3)
    {
        $vaccines = Vaccine::all();
        foreach ($vaccines as $vaccine){
            for ($i = 0; $i < $numberOfBatchesPerVaccine; $i++) {
                $vaccine->batches()->make([
                    "code" => sprintf('%s%s%s', $vaccine->name, '_batch_', $i),
                ])->saveOrFail();
            }
        }
    }
}
