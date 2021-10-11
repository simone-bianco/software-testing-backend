<?php

namespace Tests\Feature\Blackbox;

use App\Models\Patient;
use App\Models\Reservation;
use App\Models\Responsible;
use App\Models\Structure;
use App\Repositories\PatientRepository;
use Carbon\Carbon;
use Laravel\Sanctum\NewAccessToken;
use Session;
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
class PatientCreatesReservationTest extends ReservationTestCase
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

    public function testPatientCreatesReservation()
    {
        $reservationDay = Carbon::now()->addDays(5)->format('Y-m-d');

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token->plainTextToken
        ])->postJson(
            "/api/reservation",
            [
                'patient_id' => $this->patient->id,
                'date' => $reservationDay,
                'structure_id' => $this->structure->id
            ]
        )->assertStatus(Response::HTTP_OK);

        $reservation = Reservation::first();

        /** Post Condizioni */
        $this->assertDatabaseCount('reservations', 1);
        $this->assertNotNull($reservation);
        $this->assertEquals($this->patient->id, $reservation->patient_id);
        $this->assertEquals('08:00', $reservation->time->format('H:i'));
        $this->assertEquals($this->structure->id, $reservation->stock->structure_id);
        $this->assertEquals($reservationDay, $reservation->date->format('Y-m-d'));
    }
}
