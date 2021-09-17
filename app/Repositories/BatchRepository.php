<?php

namespace App\Repositories;

use App\Models\Batch;
use App\Models\Vaccine;
use App\Validators\BatchValidator;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Log;
use Str;

/**
 * Class BatchRepository
 * @package App\Repositories
 */
class BatchRepository
{
    protected BatchValidator $batchValidator;

    /**
     * BatchRepository constructor.
     * @param  BatchValidator  $batchValidator
     */
    public function __construct(
        BatchValidator $batchValidator
    ) {
        $this->batchValidator = $batchValidator;
    }

    /**
     * @param  string  $code
     * @return Batch
     */
    public function get(string $code): Batch
    {
        try {
            $batch = Batch::where('code', '=', $code)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException($e->getMessage());
        }

        return $batch;
    }

    /**
     * @param  Batch  $batch
     * @param  Vaccine|int|null  $vaccine
     * @return Batch
     * @throws ValidationException
     * @throws Exception
     */
    public function saveOrCreate(Batch $batch, $vaccine): Batch
    {
        try {
            if ($vaccine) {
                if (is_int($vaccine)) {
                    $batch->vaccine_id = $vaccine;
                } else if (is_a($vaccine, Vaccine::class)) {
                    $batch->vaccine_id = $vaccine->id;
                } else {
                    throw new Exception("Tipo vaccino non valido");
                }
            }

            return $this->assignAndSave(
                $batch->id
                    ? Batch::where('id', '=', $batch->id) : Batch::factory()->newModel(),
                $batch
            );
        } catch (ValidationException $validationException) {
            Log::error("Batch saveOrCreate Validation:\n" . $validationException->getMessage());
            Log::error(print_r($validationException->errors(), true));
            throw $validationException;
        } catch (Exception $exception) {
            Log::error("Batch saveOrCreate:\n" . $exception->getMessage());
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @param  Batch  $newBatch
     * @param  Batch  $batch
     * @return Batch
     * @throws ValidationException
     */
    private function assignAndSave(Batch $newBatch, Batch $batch): Batch
    {
        $newBatch->code = $batch->code ?? Str::random(8);
        $newBatch->vaccine_id = $batch->vaccine_id;

        $this->batchValidator->validateData($newBatch->toArray(), ['batch' => $newBatch]);
        $newBatch->save();

        return $this->get($newBatch->code);
    }
}
