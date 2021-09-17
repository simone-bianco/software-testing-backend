<?php

namespace App\Repositories;

use App\Models\Vaccine;
use App\Validators\VaccineValidator;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Log;

/**
 * Class VaccineRepository
 * @package App\Repositories
 */
class VaccineRepository
{
    protected VaccineValidator $vaccineValidator;

    /**
     * VaccineRepository constructor.
     * @param  VaccineValidator  $vaccineValidator
     */
    public function __construct(
        VaccineValidator $vaccineValidator
    ) {
        $this->vaccineValidator = $vaccineValidator;
    }

    /**
     * @param  string  $name
     * @return Vaccine
     */
    public function get(string $name): Vaccine
    {
        try {
            $vaccine = Vaccine::where('name', $name)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException($e->getMessage());
        }

        return $vaccine;
    }

    /**
     * @param  Vaccine  $vaccine
     * @return Vaccine
     * @throws ValidationException
     * @throws Exception
     */
    public function saveOrCreate(Vaccine $vaccine): Vaccine
    {
        try {
            return $this->assignAndSave(
                $vaccine->id
                    ? Vaccine::where('id', '=', $vaccine->id) : Vaccine::factory()->newModel(),
                $vaccine
            );
        } catch (ValidationException $validationException) {
            Log::error("Vaccine saveOrCreate Validation:\n" . $validationException->getMessage());
            Log::error(print_r($validationException->errors(), true));
            throw $validationException;
        } catch (Exception $exception) {
            Log::error("Vaccine saveOrCreate:\n" . $exception->getMessage());
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @param  Vaccine  $newVaccine
     * @param  Vaccine  $vaccine
     * @return Vaccine
     * @throws ValidationException
     */
    private function assignAndSave(Vaccine $newVaccine, Vaccine $vaccine): Vaccine
    {
        $newVaccine->name = $vaccine->name;
        $newVaccine->vaccine_doses = $vaccine->vaccine_doses;
        $newVaccine->src = $vaccine->src;
        $newVaccine->lazy_src = $vaccine->lazy_src;
        $newVaccine->url = $vaccine->url;

        $this->vaccineValidator->validateData($newVaccine->toArray(), ['vaccine' => $newVaccine]);
        $newVaccine->save();

        return $this->get($newVaccine->name);
    }
}
