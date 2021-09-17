<?php

namespace Database\Seeders;

use App\Models\Structure;
use App\Repositories\StructureRepository;
use Database\Factories\StructureFactory;
use File;
use Illuminate\Database\Seeder;
use Str;

class StructuresSeeder extends Seeder
{
    const DEFAULT_DELIMITER = ',';
    protected StructureRepository $structureRepository;

    /**
     * StructuresSeeder constructor.
     * @param  StructureRepository  $structureRepository
     */
    public function __construct(StructureRepository $structureRepository)
    {
        $this->structureRepository = $structureRepository;
    }

    /**
     * @param  int|null  $maxStructures
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \Throwable
     */
    public function run(?int $maxStructures = null)
    {
        /** @var resource $csv */
        $csv = File::get('database/seeders/files/strutture.csv');

        $csv = Str::remove(["'", '"'], $csv);
        $rows = explode("\n", $csv);
        $keys = explode(self::DEFAULT_DELIMITER, $rows[0]);

        foreach ($rows as $index => $row) {
            try {
                if ($index === 0) {
                    continue;
                }

                if ($index >= $maxStructures) {
                    return;
                }
                $row = explode(self::DEFAULT_DELIMITER, $row);
                $structure = array_combine($keys, $row);

                $this->structureRepository->save(StructureFactory::new()->make([
                    'name' => $structure['denominazione_struttura'],
                    'region' => $structure['nome_area'],
//                    'capacity' => 24 * random_int(4, 20)
                    'capacity' => 24,
                ]));
            } catch (\Exception $exception) {
                var_dump($exception->getMessage());
            }
        }
    }
}
