<?php

namespace Tests\Feature\Security;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Response;
use Tests\SecurityTestCase;
use Throwable;

class ResponsibleSecurityTest extends SecurityTestCase
{
    /**
     * @throws BindingResolutionException
     * @throws Throwable
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testResponsibleCannotEditOtherStructuresReservations()
    {
        foreach ($this->otherStructures as $structure) {
            $protectedReservation = $this->getFirstReservation($structure);
            $this->be($this->unauthorizedResponsible->account->user);
            \Session::put('2fa', true);

            $response = $this->call(
                'GET',
                "/prenotazione/$protectedReservation->id/edit"
            );
            $response->assertStatus(\Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN);
        }
    }

    public function testResponsibleCannotUpdateOtherStructuresReservations()
    {
        foreach ($this->otherStructures as $structure) {
            $protectedReservation = $this->getFirstReservation($structure);
            $this->be($this->unauthorizedResponsible->account->user);
            \Session::put('2fa', true);
            $response = $this->call(
                'PUT',
                "/prenotazione/$protectedReservation->id/update"
            );
            $response->assertStatus(Response::HTTP_FORBIDDEN);
        }
    }

    public function testResponsibleCannotStoreReservationsInOtherStructures()
    {
        foreach ($this->otherStructures as $structure) {
            $this->be($this->unauthorizedResponsible->account->user);
            \Session::put('2fa', true);
            $response = $this->call(
                'POST',
                "/prenotazione/salva",
                ['structure' => $structure->name]
            );
            $response->assertStatus(Response::HTTP_FORBIDDEN);
        }
    }

    public function testResponsibleCannotPollOtherStructures()
    {
        foreach ($this->otherStructures as $structure) {
            $this->be($this->unauthorizedResponsible->account->user);
            \Session::put('2fa', true);
            $response = $this->call(
                'POST',
                "/prenotazioni/reservations-polling",
                ['structure_id' => $structure->id]
            );
            $response->assertStatus(Response::HTTP_FORBIDDEN);
        }
    }

    public function testResponsibleCannotCallPatientApi()
    {
        $this->be($this->unauthorizedResponsible->account->user)->assertAuthenticated();

        $this->call(
            'GET',
            'api/get-structures-by-region/Abruzzo'
        )->assertStatus(Response::HTTP_FORBIDDEN);

        $this->call(
            'POST',
            'api/reservation',
            ['reservation' => []]
        )->assertStatus(Response::HTTP_FORBIDDEN);

        $this->call(
            'GET',
            'api/get-last-reservation-by-patient-email/test@email.it'
        )->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testResponsibleCannotLoginByApi()
    {
        $this->withHeaders([
            'Accept' => 'application/json'
        ])->postJson(
            '/api/login',
            [
                'email' => $this->unauthorizedResponsible->email,
                'password' => 'test'
            ]
        )->assertStatus(\Illuminate\Http\Response::HTTP_UNAUTHORIZED);
    }

    public function testResponsibleWithValidTokenCannotCallApi()
    {
        $this->clearTokens($this->unauthorizedResponsible->account->user);
        $token = $this->unauthorizedResponsible->account->user->createToken('app-token', [$this->unauthorizedResponsible->email]);

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ])->getJson(
            '/api/get-structures-by-region/Abruzzo'
        )->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ])->getJson(
            "/api/get-last-reservation-by-patient-email/{$this->patientCreator->execute()->email}"
        )->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ])->postJson(
            '/api/reservation',
            []
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testResponsibleWithInvalidTokenCannotCallApi()
    {
        $this->clearTokens($this->unauthorizedResponsible->account->user);

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . 'invalid_token'
        ])->getJson(
            '/api/get-structures-by-region/Abruzzo'
        )->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . 'invalid_token'
        ])->getJson(
            "/api/get-last-reservation-by-patient-email/{$this->patientCreator->execute()->email}"
        )->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . 'invalid_token'
        ])->postJson(
            '/api/reservation',
            []
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
