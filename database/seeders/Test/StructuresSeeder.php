<?php

namespace Database\Seeders\Test;

use App\Repositories\StructureRepository;
use Database\Factories\StructureFactory;
use Illuminate\Database\Seeder;
use Illuminate\Validation\ValidationException;
use Throwable;

class StructuresSeeder extends Seeder
{
    protected StructureRepository $structureRepository;

    /**
     * StructuresSeeder constructor.
     * @param  StructureRepository  $structureRepository
     */
    public function __construct(
        StructureRepository $structureRepository
    ) {
        $this->structureRepository = $structureRepository;
    }

    /**
     * @throws ValidationException
     * @throws Throwable
     */
    public function run(int $numberOfStructures = 2)
    {
        for ($i = 0; $i < $numberOfStructures; $i++) {
            $this->structureRepository->save(StructureFactory::new()->make([
                'name' => "Struttura Test $i",
                'region' => 'campania',
                'capacity' => 24,
            ]));
        }
    }
}
