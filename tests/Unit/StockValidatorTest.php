<?php

namespace Tests\Unit;

use App\Models\Batch;
use App\Models\Stock;
use App\Models\Structure;
use App\Validators\StockValidator;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\BaseTestCase;

/**
 * @group reservationIntegration
 */
class StockValidatorTest extends BaseTestCase
{
    protected ?StockValidator $stockValidator;

    public function setUp(): void
    {
        parent::setUp();
        $this->stockValidator = app(StockValidator::class);
    }

    /**
     * @dataProvider stockProvider
     */
    public function testStockValidator(
        $expectedResult,
        $quantity,
        $code,
        $structure,
        $batch,
        $fieldsWithError
    ) {
        if ($structure === 'first') {
            $structure = Structure::firstOrFail()->id;
        }

        if ($batch === 'first') {
            $batch = Batch::firstOrFail()->id;
        }

        $stockData = [
            'quantity' => $quantity,
            'code' => $code,
            'structure_id' => $structure,
            'batch_id' => $batch,
        ];

        try {
            $this->stockValidator->validateData(
                $stockData,
                ['stock' => Stock::make($stockData)]
            );
            //mi aspettavo che la validation passasse?
            $this->assertEquals(
                true,
                $expectedResult,
                "Il seguente dataset doveva lanciare eccezione:\n"
                . print_r($stockData, true)
            );
        } catch (ValidationException $exception) {
            //mi aspettavo un'eccezione?
            $this->assertEquals(
                false,
                $expectedResult,
                "Si è verificata un'eccezione non prevista: '{$exception->getMessage()}', per il dataset:\n"
                . print_r($stockData, true)
            );
            //Verifico che gli errori che si sono verificati coincidano con quelli aspettati
            $errors = $exception->errors();
            $this->assertEquals(sizeof($errors), sizeof($fieldsWithError));
            foreach ($fieldsWithError as $fieldWithError) {
                $this->assertArrayHasKey($fieldWithError, $errors);
            }
        }
    }

    public function stockProvider(): array
    {
        $str = Str::random(32);
        return [
            //[Esito validation, codice, id struttura, id lotto, attributi non validi]
            #step 1
            [true, 5, $str, 'first', 'first', []],
            #step 2
            [false, null, null, null, null, ['quantity', 'code', 'structure_id', 'batch_id']],
            #step 3
            [false, -1, Str::random(500), -1, -1, ['quantity', 'code', 'structure_id', 'batch_id']],
            #step 4
            [false, PHP_INT_MAX + 1, '', 100, 100, ['quantity', 'code', 'structure_id', 'batch_id']],
            #step 5
            [false, '', -1, '', '', ['quantity', 'code', 'structure_id', 'batch_id']],
            #step 6
            [false, 5, Str::random(3), 'first', 'first', ['code']],
        ];
    }
}
