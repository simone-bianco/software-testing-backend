<?php

namespace Tests\Feature\Blackbox;

use App\Models\Patient;
use App\Models\Responsible;
use App\Models\Structure;
use App\Repositories\PatientRepository;
use Arr;
use Carbon\Carbon;
use Laravel\Sanctum\NewAccessToken;
use Symfony\Component\HttpFoundation\Response;
use Tests\ReservationTestCase;
use Throwable;

/**
 * Test sulle chiamate API da parte del paziente per la creazione delle reservation
 */
class RobustnessCreateReservationTest extends ReservationTestCase
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
        $payload = $response->json();
        $errors = Arr::get($payload, 'errors');
        if (!$errors) {
            $this->fail("Non ci sono errori nel payload");
        }
        $errorFields = array_keys($errors);
        $this->assertCount(3, $errorFields);
        $this->assertContains('patient_id', $errorFields);
        $this->assertContains('structure_id', $errorFields);
        $this->assertContains('date', $errorFields);

        /** Post Condizioni */
        $this->assertDatabaseCount('reservations', 0);
    }

    public function reservationProvider(): array
    {
        return [
            //ID paziente non valido (nullo), data in formato non valido, ID struttura non valido (nullo) #0
            [null, Carbon::now()->addDays(5)->format('d-m-Y'), null],
            //ID paziente non valido (stringa), data nulla, ID struttura non valido #1
            ["wrong ID", null, "wrong ID"],
            //ID di un paziente non esistente, data antecedente a quella attuale, ID struttura non valido #2
            [100, Carbon::now()->subDays(5)->format('Y-m-d'), 100],
        ];
    }
}
