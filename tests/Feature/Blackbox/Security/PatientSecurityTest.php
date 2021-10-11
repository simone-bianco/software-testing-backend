<?php

namespace Tests\Feature\Blackbox\Security;

use App\Models\Patient;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Response;
use Tests\SecurityTestCase;
use Throwable;

class PatientSecurityTest extends SecurityTestCase
{
    protected Patient $patient;
    protected Patient $anotherPatient;

    /**
     * @throws BindingResolutionException
     * @throws Throwable
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->patient = Patient::findOrFail(1);
        $this->anotherPatient = Patient::findOrFail(2);
    }

    public function testPatientWithValidTokenCannotGetReservationOfAnotherPatient()
    {
        $this->clearTokens($this->patient->account->user);
        $token = $this->patient->account->user->createToken('app-token', [$this->patient->email]);

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ])->getJson(
            "/api/get-last-reservation-by-patient-email/{$this->anotherPatient->email}"
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testPatientWithInvalidTokenCannotGetReservationOfAnotherPatient()
    {
        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . 'invalid_token'
        ])->getJson(
            "/api/get-last-reservation-by-patient-email/{$this->anotherPatient->email}"
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testPatientWithoutTokenCannotGetReservationOfAnotherPatient()
    {
        $this->withHeaders([
            'Accept' => 'application/json'
        ])->getJson(
            "/api/get-last-reservation-by-patient-email/{$this->anotherPatient->email}"
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testPatientWithInvalidTokenCannotGetHisReservation()
    {
        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . 'invalid_token'
        ])->getJson(
            "/api/get-last-reservation-by-patient-email/{$this->patient->email}"
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testPatientWithoutTokenCannotGetHisReservation()
    {
        $this->withHeaders([
            'Accept' => 'application/json'
        ])->getJson(
            "/api/get-last-reservation-by-patient-email/{$this->patient->email}"
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testPatientWithInvalidTokenCannotGetStructuresByRegion()
    {
        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . 'invalid_token'
        ])->getJson(
            "/api/get-structures-by-region/Abruzzo"
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testPatientWithoutTokenCannotGetStructuresByRegion()
    {
        $this->withHeaders([
            'Accept' => 'application/json'
        ])->getJson(
            "/api/get-structures-by-region/Abruzzo"
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testPatientWithValidTokenCannotCreateReservationForAnotherPatient()
    {
        $this->clearTokens($this->patient->account->user);
        $token = $this->patient->account->user->createToken('app-token', [$this->patient->email]);

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ])->postJson(
            "/api/reservation",
            ['patient_id' => $this->anotherPatient->id]
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testPatientWithInvalidTokenCannotCreateReservationForAnotherPatient()
    {
        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . 'invalid_token'
        ])->postJson(
            "/api/reservation",
            []
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testPatientWithoutTokenCannotCreateReservationForAnotherPatient()
    {
        $this->withHeaders([
            'Accept' => 'application/json'
        ])->postJson(
            "/api/reservation",
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testPatientWithInvalidTokenCannotCreateReservation()
    {
        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . 'invalid_token'
        ])->postJson(
            "/api/reservation",
            []
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testPatientWithoutTokenCannotCreateReservation()
    {
        $this->withHeaders([
            'Accept' => 'application/json'
        ])->postJson(
            "/api/reservation",
            []
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
