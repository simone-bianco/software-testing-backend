<?php

namespace Tests\Feature\Whitebox;

use App\Models\Patient;
use App\Models\Reservation;
use App\Models\Structure;
use App\Models\Vaccine;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Session;
use Tests\ReservationTestCase;


class RouteCoverageTest extends ReservationTestCase
{
    const K = 2;

    public function setUp(): void
    {
        parent::setUp();
        $this->createReservation(Structure::firstOrFail(), Patient::firstOrFail());
    }

    /**
     * @dataProvider requestProvider
     * @param $url
     * @param $method
     * @param $fields
     * @param $iterations
     */
    public function testRouteCoverageKWay($url, $method, $fields, $iterations)
    {
        # faccio chiamata senza autenticazione
        $requestBody = [];
        $structure = Structure::firstOrFail();
        $responsible = $structure->responsibles()->firstOrFail();
        $response = $this->call(
            $method,
            $url,
            $requestBody
        );
        $this->assertNotEquals(500, $response->status());
        # faccio chiamata con autenticazione ma senza 2fa
        $this->be($responsible->account->user);
        $response = $this->call(
            $method,
            $url,
            $requestBody
        );
        $this->assertNotEquals(500, $response->status());

        Session::put('2fa', true);

        $dictionaries = [];
        foreach ($fields as $field) {
            $dictionaries[$field] = $this->getDictionary($field);
        }

//        $combinations = $this->uniqueCombination($dictionaries, 1, 5);
//        var_dump(print_r($combinations, true));

        $fieldsCombinations = $this->generateKCombinations($dictionaries, 3);

        $keysByCombination = [];
        $lengthsByKeyCombinations = [];
        foreach ($fieldsCombinations as $keysCombination => $fieldsCombination) {
            $keysByCombination[$keysCombination] = explode('.', $keysCombination);
            $lengthsByKeyCombinations[$keysCombination] = sizeof($fieldsCombination);
        }
        $testsCount = 2;
        $iterations = sizeof(Arr::first($fieldsCombinations));
        for ($i = 0; $i < $iterations; $i++) {
            $payload = [];
            foreach ($keysByCombination as $keyCombination => $keys) {
                foreach ($keys as $index => $key) {
                    $payload[$key] = $dictionaries[$key][$fieldsCombinations[$keyCombination][$i % $lengthsByKeyCombinations[$keyCombination]][$index]];
                }
            }
            $response = $this->call(
                $method,
                $url,
                $payload
            );
            $this->assertNotEquals(500, $response->status());
            $testsCount++;
        }
        \Log::channel('daily')->info(print_r($testsCount, true));
    }

    /**
     * @dataProvider requestProvider
     * @param $url
     * @param $method
     * @param $fields
     * @param $iterations
     */
    public function testRouteCoverageRandom($url, $method, $fields, $iterations)
    {
        $structure = Structure::firstOrFail();
        $responsible = $structure->responsibles()->firstOrFail();
        $this->be($responsible->account->user);
        Session::put('2fa', true);

        $dictionaries = [];
        foreach ($fields as $field) {
            $dictionaries[$field] = $this->getDictionary($field);
        }

        $testsCount = 0;
        for ($i = 0; $i < $iterations; $i++) {
            $requestBody = [];
            foreach ($dictionaries as $field => $dictionary) {
                $requestBody[$field] = Arr::random($dictionary);
            }
            $response = $this->call(
                $method,
                $url,
                $requestBody
            );
            //Se ho 500 vuol dire che ho eccezioni non gestite
            $this->assertNotEquals(500, $response->status());
            $testsCount++;
        }
        \Log::channel('daily')->info(print_r($testsCount, true));
    }

    public function requestProvider(): array
    {
        return [
            ['/prenotazione/salva', 'POST', ['time', 'vaccine', 'state', 'structure', 'patient', 'date'], 100],
            ['/dashboard', 'GET', ['dummy'], 1],
            ['/prenotazioni', 'GET', array_merge(['items_per_page', 'current_page'], Reservation::getFilters()), 100],
            ['/prenotazione/1/edit', 'GET', ['dummy'], 1],
            ['/prenotazione/1/richiamo', 'GET', ['dummy'], 1],
            ['/prenotazione/busy-times', 'POST', ['date', 'reservation_id', 'structure_id'], 50],
            ['/prenotazioni/reservations-polling', 'POST', ['structure_id', 'last_update'], 50],
            ['/', 'GET', ['dummy'], 1],
            ['/login', 'GET', ['dummy'], 1],
            ['/qr/completeRegistration', 'POST', ['secret', 'code'], 50],
            ['/qr/register', 'GET', ['dummy'], 1],
            ['/qr/authenticate', 'GET', ['dummy'], 1],
            ['/qr/completeLogin', 'POST', ['code'], 1],
        ];
    }

    protected function getDictionary(?string $field): array
    {
        if ($field === 'dummy') {
            return [null];
        }
        $baseDictionary = [
            -1, 1, 0,
            Str::random(50), Str::random(1000),
            '', null
        ];
        if (!$field) {
            return $baseDictionary;
        }
        if ($field === 'date' || $field === 'last_update') {
            $now = Carbon::now();
            $pastDate = $now->subDays(5);
            $futureDate = $now->addDays(5);
            $extensionDictionary = [
                $now, $pastDate, $futureDate, $now->format('Y-m-d'), $pastDate->format('Y-m-d'),
                $futureDate->format('Y-m-d')
            ];
            return array_merge($baseDictionary, $extensionDictionary);
        }
        if ($field === 'time') {
            $extensionDictionary = [
                '00:00', '12:00', '17:00', '25:00'
            ];
            return array_merge($baseDictionary, $extensionDictionary);
        }
        if ($field === 'vaccine') {
            $extensionDictionary = [
                Vaccine::all()->pluck('name')->toArray()
            ];
            return array_merge($baseDictionary, Arr::first($extensionDictionary));
        }
        if ($field === 'state') {
            $extensionDictionary = [
                Reservation::getStates()
            ];
            return array_merge($baseDictionary, $extensionDictionary);
        }
        if ($field === 'structure') {
            $extensionDictionary = [
                Structure::firstOrFail()->name
            ];
            return array_merge($baseDictionary, $extensionDictionary);
        }
        return $baseDictionary;
    }
}
