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
    public function run()
    {
        $this->structureRepository->save(StructureFactory::new()->make([
            'name' => 'Struttura Test 1',
            'region' => 'campania',
            'capacity' => 24,
        ]));
        $this->structureRepository->save(StructureFactory::new()->make([
            'name' => 'Struttura Test 2',
            'region' => 'abruzzo',
            'capacity' => 24,
        ]));
    }
}
