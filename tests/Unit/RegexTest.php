<?php

namespace Tests\Unit;

use File;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Validator;

/**
 * Test Data Driven che verifica la correttezza delle regex
 */
class RegexTest extends TestCase
{
    protected const BASE_PATH = 'tests/Unit/DataProviders/';

    /**
     * @dataProvider regexProvider
     */
    public function testRegexValidation($fileName)
    {
        $filePath = self::BASE_PATH . $fileName;
        $jsonContent = json_decode(File::get($filePath));
        $expression = $jsonContent[0];
        $validValues = $jsonContent[1];
        $invalidValues = $jsonContent[2];

        foreach ($validValues as $validValue) {
            try {
                $validatedData = Validator::make(
                    ['regex' => $validValue],
                    ['regex' => $expression],
                    ['regex.*' => 'Regex non valida!']
                )->validate();
                $this->assertNotNull($validatedData);
            } catch (ValidationException $validationException) {
                $this->assertFalse(true, "$expression -> $validValue risulta non valida!\n");
            }
        }
        foreach ($invalidValues as $invalidValue) {
            try {
                Validator::make(
                    ['regex' => $invalidValue],
                    ['regex' => $expression],
                    ['regex.*' => 'Regex non valida!']
                )->validate();
                $this->assertFalse(true, "$expression -> $invalidValue passa!");
            } catch (ValidationException $exception) {
                $this->assertNotNull($exception);
            }
        }
    }

    public function regexProvider()
    {
        return [
            ['fiscal_code.json'],
            ['vaccine_name.json'],
        ];
    }
}
