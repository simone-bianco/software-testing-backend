<?php

namespace App\Repositories;

use App\Models\Structure;
use App\Validators\StructureValidator;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Log;
use Throwable;

/**
 * Class StructureRepository
 * @package App\Repositories
 */
class StructureRepository
{
    protected StructureValidator $structureValidator;

    /**
     * StructureRepository constructor.
     * @param  StructureValidator  $structureValidator
     */
    public function __construct(
        StructureValidator $structureValidator
    ) {
        $this->structureValidator = $structureValidator;
    }

    /**
     * @param  string  $name
     * @return Structure
     */
    public function get(string $name): Structure
    {
        try {
            $structure = Structure::where('name', $name)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException($e->getMessage());
        }

        return $structure;
    }

    /**
     * @param  Structure  $structure
     * @return Structure
     * @throws ValidationException
     * @throws Throwable
     */
    public function save(Structure $structure): Structure
    {
        $this->structureValidator->validateData($structure->toArray(), ['structure' => $structure]);
        $structure->saveOrFail();

        return Structure::where('id', '=', $structure->id)->firstOrFail();
    }

    /**
     * @param  Structure  $structure
     * @return Structure
     * @throws ValidationException
     * @throws Exception
     */
    public function saveOrCreate(Structure $structure): Structure
    {
        try {
            return $this->assignAndSave(
                $structure->id
                    ? Structure::where('id', '=', $structure->id) : Structure::factory()->newModel(),
                $structure
            );
        } catch (ValidationException $validationException) {
            Log::error("Structure saveOrCreate Validation:\n" . $validationException->getMessage());
            Log::error(print_r($validationException->errors(), true));
            throw $validationException;
        } catch (Exception $exception) {
            Log::error("Structure saveOrCreate:\n" . $exception->getMessage());
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @param  Structure  $newStructure
     * @param  Structure  $structure
     * @return Structure
     * @throws ValidationException
     */
    private function assignAndSave(Structure $newStructure, Structure $structure): Structure
    {
        $newStructure->name = $structure->name;
        $newStructure->phone_number = $structure->phone_number;
        $newStructure->capacity = $structure->capacity;
        $newStructure->region = $structure->region;
        $newStructure->address = $structure->address;

        $this->structureValidator->validateData($newStructure->toArray(), ['structure' => $newStructure]);
        $newStructure->save();

        return $this->get($newStructure->name);
    }
}
