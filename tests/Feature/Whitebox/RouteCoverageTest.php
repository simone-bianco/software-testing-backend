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
    const K = 3;

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
        $requestBody = [];
        $structure = Structure::firstOrFail();
        $responsible = $structure->responsibles()->firstOrFail();
        $response = $this->call(
            $method,
            $url,
            $requestBody
        );
        $this->assertNotEquals(500, $response->status());
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

        foreach ($dictionaries as $field => $dictionary) {
            if (sizeof($dictionary) < 1) {
                unset($dictionaries[$field]);
                continue;
            }
            $requestBody[$field] = Arr::first($dictionary);
            unset($dictionaries[$field][0]);
        }
        while (sizeof($dictionaries) > 0) {
            $response = $this->call(
                $method,
                $url,
                $requestBody
            );
            for ($k = 0; $k < self::K; $k++) {
                foreach ($dictionaries as $field => $dictionary) {
                    if (sizeof($dictionary) < 1) {
                        unset($dictionaries[$field]);
                        continue;
                    }
                    $requestBody[$field] = Arr::first($dictionary);
                    unset($dictionaries[$field][array_key_first($dictionary)]);
                    break;
                }
            }
            //Se ho 500 vuol dire che ho eccezioni non gestite
            $this->assertNotEquals(500, $response->status());
        }
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
        }
    }

    public function requestProvider(): array
    {
        return [
            ['/dashboard', 'GET', ['dummy'], 1],
            ['/prenotazioni', 'GET', array_merge(['items_per_page', 'current_page'], Reservation::getFilters()), 100],
            ['/prenotazione/salva', 'POST', ['date', 'time', 'vaccine', 'state', 'structure', 'patient'], 100],
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
