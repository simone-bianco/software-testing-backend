<?php

namespace App\Repositories;

use App\Models\Stock;
use App\Validators\StockValidator;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Log;
use Str;

/**
 * Class StockRepository
 * @package App\Repositories
 */
class StockRepository
{
    protected StockValidator $stockValidator;

    /**
     * StockRepository constructor.
     * @param  StockValidator  $stockValidator
     */
    public function __construct(
        StockValidator $stockValidator
    ) {
        $this->stockValidator = $stockValidator;
    }

    /**
     * @param  string  $code
     * @return Stock
     */
    public function get(string $code): Stock
    {
        try {
            $stock = Stock::where('code', '=', $code)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException($e->getMessage());
        }

        return $stock;
    }

    /**
     * @param  Stock  $stock
     * @param  int  $qty
     * @return Stock
     * @throws ValidationException
     */
    public function incrementAndSave(Stock $stock, int $qty = 1): Stock
    {
        try {
            $stock->quantity += $qty;

            return $this->assignAndSave(
                Stock::whereId($stock->id)->firstOrFail(),
                $stock
            );
        } catch (ValidationException $validationException) {
            Log::error("Stock incrementAndSave Validation:\n" . $validationException->getMessage());
            Log::error(print_r($validationException->errors(), true));
            throw $validationException;
        } catch (Exception $exception) {
            Log::error("Stock incrementAndSave:\n" . $exception->getMessage());
            throw $exception;
        }
    }

//    public function decrementAndSave($stock, int $qty = 1): Stock
    public function decrementAndSave(Stock $stock, int $qty = 1): Stock
    {
        try {
//            $stockAttributes = $stock->toArray();
            $stock->quantity -= $qty;

            return $this->assignAndSave(
//                Stock::whereId($stock->id)->first(),
                Stock::whereId($stock->id)->firstOrFail(),
                $stock
            );
        } catch (ValidationException $validationException) {
            Log::error("Stock decrementAndSave Validation:\n" . $validationException->getMessage());
            Log::error(print_r($validationException->errors(), true));
            throw $validationException;
        }
    }

    /**
     * @param  Stock  $stock
     * @return Stock
     * @throws ValidationException
     */
    public function create(Stock $stock): Stock
    {
        try {
            $stock->code = Str::random(32);

            return $this->assignAndSave(
                Stock::factory()->make(),
                $stock
            );
        } catch (ValidationException $validationException) {
            Log::error("Stock decrementAndSave Validation:\n" . $validationException->getMessage());
            Log::error(print_r($validationException->errors(), true));
            throw $validationException;
        } catch (Exception $exception) {
            Log::error("Stock decrementAndSave:\n" . $exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param  Stock  $newStock
     * @param  Stock  $stock
     * @return Stock
     * @throws ValidationException
     */
    private function assignAndSave(Stock $newStock, Stock $stock): Stock
    {
        $newStock->code = $stock->code;
        $newStock->batch_id = $stock->batch_id;
        $newStock->structure_id = $stock->structure_id;
        $newStock->quantity = $stock->quantity;

        $this->stockValidator->validateData($newStock->toArray(), ['stock' => $newStock]);
        $newStock->save();

        return $this->get($newStock->code);
    }
}
