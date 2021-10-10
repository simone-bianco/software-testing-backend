<?php
//
//namespace Tests\Feature\Blackbox;
//
//use App\Models\Patient;
//use App\Models\Reservation;
//use App\Models\Structure;
//use App\Models\User;
//use App\Repositories\PatientRepository;
//use Carbon\Carbon;
//use DB;
//use Symfony\Component\HttpFoundation\Response;
//use Tests\ReservationTestCase;
//use Throwable;
//
///**
// * Test sulle chiamate API da parte del paziente per la creazione delle reservation
// */
//class PatientCreatesReservationTest extends ReservationTestCase
//{
//    protected PatientRepository $patientRepository;
//
//    /**
//     * @throws Throwable
//     */
//    public function setUp() : void
//    {
//        parent::setUp();
//        DB::beginTransaction();
//    }
//
//    /**
//     * @throws Throwable
//     */
//    public function tearDown(): void
//    {
//        DB::rollback();
//        parent::tearDown();
//    }
//
//    public function testPatientCreatesReservation()
//    {
//        $patient = Patient::first();
//        $this->assertNotNull($patient);
//
//        $reservations = $patient->reservations;
//        $this->assertCount(0, $reservations);
//
//        $structure = Structure::first();
//        $this->assertNotNull($structure);
//
//        $this->clearTokens($patient->account->user);
//        $token = $patient->account->user->createToken('app-token', [$patient->email]);
//
//        $reservationDay = Carbon::now()->addDays(5)->format('Y-m-d');
//
//        $this->withHeaders([
//            'Accept' => 'application/json',
//            'Authorization' => 'Bearer ' . $token->plainTextToken
//        ])->postJson(
//            "/api/reservation",
//            [
//                'patient_id' => $patient->id,
//                'date' => $reservationDay,
//                'structure_id' => $structure->id
//            ]
//        )->assertStatus(Response::HTTP_OK);
//
//        $this->assertDatabaseCount('reservations', 1);
//        $reservation = Reservation::first();
//        $this->assertNotNull($reservation);
//        $this->assertEquals($patient->id, $reservation->patient_id);
//        $this->assertEquals($structure->id, $reservation->stock->structure_id);
//        $this->assertEquals($reservationDay, $reservation->date->format('Y-m-d'));
//    }
//
//    protected function clearTokens(User $user)
//    {
//        $user->tokens()->where('name', '=', 'app-token')->delete();
//    }
//}
