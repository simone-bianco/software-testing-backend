<?php

namespace Tests\Unit;

use App\Models\Patient;
use App\Models\Reservation;
use App\Models\Stock;
use App\Validators\ReservationValidator;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\BaseTestCase;

/**
 * @group reservationIntegration
 */
class ReservationValidatorTest extends BaseTestCase
{
    protected ?ReservationValidator $reservationValidator;

    public function setUp(): void
    {
        parent::setUp();
        $this->reservationValidator = app(ReservationValidator::class);
    }

    /**
     * @dataProvider reservationProvider
     */
    public function testReservationValidatorTest(
        $expectedResult,
        $date,
        $state,
        $time,
        $notes,
        $code,
        $patient,
        $stock,
        $fieldsWithError
    ) {
        if ($patient === 'first') {
            $patient = Patient::firstOrFail()->id;
        }

        if ($stock === 'first') {
            $stock = Stock::firstOrFail()->id;
        }

        $reservationData = [
            'date' => $date,
            'state' => $state,
            'time' => $time,
            'notes' => $notes,
            'code' => $code,
            'patient_id' => $patient,
            'stock_id' => $stock,
        ];

        try {
            $this->reservationValidator->validateData(
                $reservationData,
                ['reservation' => Reservation::make($reservationData)]
            );
            $this->assertEquals(
                true,
                $expectedResult,
                "Il seguente dataset doveva lanciare eccezione:\n"
                . print_r($reservationData, true)
            );
        } catch (ValidationException $exception) {
            //mi aspettavo un'eccezione?
            $this->assertEquals(
                false,
                $expectedResult,
                "Si è verificata un'eccezione non prevista: '{$exception->getMessage()}', per il dataset:\n"
                . print_r($reservationData, true)
            );
            //Verifico che gli errori che si sono verificati coincidano con quelli aspettati
            $errors = $exception->errors();
            $this->assertEquals(sizeof($errors), sizeof($fieldsWithError), print_r($errors, true));
            foreach ($fieldsWithError as $fieldWithError) {
                $this->assertArrayHasKey($fieldWithError, $errors);
            }
        }
    }

    public function reservationProvider(): array
    {
        $pastDate = Carbon::now()->subDays(5)->format('Y-m-d');
        $futureDate = Carbon::now()->addDays(5)->format('Y-m-d');
        $str = Str::random(32);
        $validState = Arr::first(Reservation::getStates());
        return [
            //Esito validation, data, stato, orario, note, codice, id paziente, id stock, campi non validi
            //step 1
            [true, $futureDate, $validState, '12:00', 'note', $str, 'first', 'first', []],
            //step 2
            [false, null, null, null, null, null, null, null,
                ['date', 'state', 'time', 'notes', 'code', 'patient_id', 'stock_id']],
            //step 3
            [false, '', '', '', '', '', '', '',
                ['date', 'state', 'time', 'code', 'patient_id', 'stock_id']],
            //step 4
            [false, $pastDate, "INVALID_STATE", '26:00', Str::random(257), Str::random(257), -1, -1,
                ['date', 'state', 'time', 'notes', 'code', 'patient_id', 'stock_id']],
            //step 5
            [false, $pastDate, $validState, 'xxfa00', 'notes', '"çFé_#++', 100, 100,
                ['date', 'time', 'code', 'patient_id', 'stock_id']],
        ];
    }
}
