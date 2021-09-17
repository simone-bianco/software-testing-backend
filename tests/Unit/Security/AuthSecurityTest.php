<?php

namespace Tests\Unit\Security;

use App\Models\User;
use App\Repositories\UserRepository;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Response;
use Tests\SecurityTestCase;
use Throwable;

class AuthSecurityTest extends SecurityTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testGuestCannotVisitBackoffice()
    {
        $this->call(
            'GET',
            route('dashboard.index')
        )->assertRedirect('login');

        $this->call(
            'GET',
            route('reservations.index')
        )->assertRedirect('login');

        $this->call(
            'GET',
            route('reservations.edit', ['reservation' => 1])
        )->assertRedirect('login');

        $this->call(
            'GET',
            route('reservations.create', ['reservation' => 1])
        )->assertRedirect('login');

        $this->call(
            'POST',
            route('reservations.poll')
        )->assertRedirect('login');

        $this->call(
            'POST',
            route('reservations.store', ['reservation' => 1])
        )->assertRedirect('login');

        $this->call(
            'POST',
            route('reservations.busytimes')
        )->assertRedirect('login');

        $this->call(
            'PUT',
            route('reservations.update', ['reservation' => 1])
        )->assertRedirect('login');
    }

    public function testGuestWithoutTokenCannotCallApi()
    {
        $this->withHeaders([
            'Accept' => 'application/json'
        ])->getJson(
            '/api/get-structures-by-region/Abruzzo'
        )->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->withHeaders([
            'Accept' => 'application/json'
        ])->getJson(
            "/api/get-last-reservation-by-patient-email/{$this->patientCreator->execute()->email}"
        )->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->withHeaders([
            'Accept' => 'application/json'
        ])->postJson(
            '/api/reservation',
            []
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    protected function getUnassociatedUserData(): array
    {
        return [
            'email' => 'unassociated.user@email.it',
            'name' => 'unassociated user',
            'password' => 'test'
        ];
    }
}
