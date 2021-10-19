<?php

namespace Tests\Feature\Blackbox\Security;

use App\Models\Patient;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Response;
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
        $token = $this->patient->account->user->createToken('app-token', [$this->patient->email]);

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ])->getJson(
            "/api/get-last-reservation-by-patient-email/{$this->anotherPatient->email}",
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testPatientWithValidCanGetHisReservation()
    {
        $token = $this->patient->account->user->createToken('app-token', [$this->patient->email]);

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ])->getJson(
            "/api/get-last-reservation-by-patient-email/{$this->patient->email}",
        )->assertStatus(Response::HTTP_OK);
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
}
