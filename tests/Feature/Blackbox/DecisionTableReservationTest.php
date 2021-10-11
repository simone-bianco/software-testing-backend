<?php

namespace Tests\Feature\Blackbox;

use App\Models\Patient;
use App\Models\Responsible;
use App\Models\Structure;
use App\Repositories\PatientRepository;
use Carbon\Carbon;
use Laravel\Sanctum\NewAccessToken;
use Symfony\Component\HttpFoundation\Response;
use Tests\ReservationTestCase;
use Throwable;

/**
 * Test sulle chiamate API da parte del paziente per la creazione delle reservation
 * @covers \App\Http\Controllers\ReservationController
 * @covers \App\Repositories\ReservationRepository
 * @covers \App\Models\Reservation
 * @covers \App\Models\Stock
 */
class DecisionTableReservationTest extends ReservationTestCase
{
    protected ?Structure $structure = null;
    protected ?Responsible $responsible = null;
    protected ?Patient $patient = null;
    protected ?PatientRepository $patientRepository = null;
    protected ?NewAccessToken $token = null;

    /**
     * @throws Throwable
     */
    public function setUp() : void
    {
        parent::setUp();
        $this->structure = Structure::first();
        /** @var Responsible $responsible */
        $responsible = $this->structure->responsibles()->first();
        $this->responsible = $responsible;
        $this->patient = Patient::first();
        $this->token = $this->patient->account->user->createToken('app-token', [$this->patient->email]);
    }

    protected function assertPreConditions(): void
    {
        parent::assertPreConditions();
        $this->assertNotNull($this->structure);
        $this->assertNotNull($this->responsible);
        $this->assertNotNull($this->patient);
        $this->assertNotNull($this->token);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->patient->account->user->tokens()->where('name', '=', 'app-token')->delete();
    }

    /**
     * @dataProvider reservationProvider
     * @param $patientId
     * @param $date
     * @param $structureId
     */
    public function testPatientCannotCreateReservation($patientId, $date, $structureId)
    {
        if ($date instanceof Carbon) {
            $date = $date->format('Y-m-d');
        }
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token->plainTextToken
        ])->postJson(
            "/api/reservation",
            [
                'patient_id' => $patientId,
                'date' => $date,
                'structure_id' => $structureId
            ]
        );
        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);

        /** Post Condizioni */
        $this->assertDatabaseCount('reservations', 0);
    }

    public function reservationProvider(): array
    {
        return [
            //ID paziente non valido (nullo) #0
            [null, Carbon::now()->addDays(5), 1],
            //ID paziente non valido (stringa) #1
            ["wrong ID", Carbon::now()->addDays(5), 1],
            //ID paziente non valido (zero) #2
            [0, Carbon::now()->addDays(5), 1],
            //ID di un paziente non esistente #3
            [100, Carbon::now()->addDays(5), 1],
            //data non valida #4
            [1, "wrong date", 1],
            //mese non valido #5
            [1, "2021-22-01", 1],
            //giorno non valido #6
            [1, "2021-11-32", 1],
            //anno non valido #7
            [1, "XXXX-11-32", 1],
            //Data precedente a quella corrente #8
            [1, Carbon::now()->subDays(5), 1],
            //Data coincidente con quella attuale #9
            [1, Carbon::now()->startOfDay(), 1],
            //Data nulla #10
            [1, null, 1],
            //Data vuota #11
            [1, "", 1],
            //ID struttura non valido (nullo) #12
            [1, Carbon::now()->addDays(5), null],
            //ID struttura non valido (stringa) #13
            [1, Carbon::now()->addDays(5), "struttura"],
            //ID struttura non valido (zero) #14
            [1, Carbon::now()->addDays(5), 0],
            //ID di una struttura non esistente #15
            [1, Carbon::now()->addDays(5), 100],
        ];
    }
}
