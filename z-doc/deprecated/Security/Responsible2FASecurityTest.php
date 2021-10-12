<?php

namespace Tests\Feature\Blackbox\Security;

use Illuminate\Contracts\Container\BindingResolutionException;
use Session;
use Tests\SecurityTestCase;
use Throwable;

class Responsible2FASecurityTest extends SecurityTestCase
{
    /**
     * @throws BindingResolutionException
     * @throws Throwable
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testResponsibleLoggedWithoutOtpCannotEditStructuresReservations()
    {
        $user = $this->unauthorizedResponsible->account->user;
        $this->be($user);
        Session::put('2fa', false);
        $structure = $user->responsible->structure;
        $protectedReservation = $this->getFirstReservation($structure);
        $response = $this->call(
            'GET',
            "/prenotazione/$protectedReservation->id/edit"
        );
        $response->assertRedirect(route('2fa.authenticate'));
    }

    public function testResponsibleLoggedWithoutOtpCannotUpdateStructuresReservations()
    {
        $user = $this->unauthorizedResponsible->account->user;
        $this->be($user);
        Session::put('2fa', false);
        $structure = $user->responsible->structure;
        $protectedReservation = $this->getFirstReservation($structure);
        $response = $this->call(
            'PUT',
            "/prenotazione/$protectedReservation->id/update"
        );
        $response->assertRedirect(route('2fa.authenticate'));
    }

    public function testResponsibleLoggedWithoutOtpCannotStoreReservations()
    {
        $user = $this->unauthorizedResponsible->account->user;
        $this->be($user);
        Session::put('2fa', false);
        $structure = $user->responsible->structure;
        $response = $this->call(
            'POST',
            "/prenotazione/salva",
            ['structure' => $structure->name]
        );
        $response->assertRedirect(route('2fa.authenticate'));
    }

    public function testResponsibleLoggedWithoutOtpCannotPoll()
    {
        $user = $this->unauthorizedResponsible->account->user;
        $this->be($user);
        Session::put('2fa', false);
        $structure = $user->responsible->structure;
        $response = $this->call(
            'POST',
            "/prenotazioni/reservations-polling",
            ['structure_id' => $structure->id]
        );
        $response->assertRedirect(route('2fa.authenticate'));
    }
}
