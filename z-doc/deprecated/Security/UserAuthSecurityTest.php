<?php

namespace Tests\Feature\Blackbox\Security;

use App\Models\User;
use App\Repositories\UserRepository;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Response;
use Tests\SecurityTestCase;
use Throwable;

class UserAuthSecurityTest extends SecurityTestCase
{
    protected UserRepository $userRepository;

    protected User $unassociatedUser;

    /**
     * @throws BindingResolutionException
     * @throws Throwable
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->app->make(UserRepository::class);
        $this->unassociatedUser = $this->userRepository->saveOrCreate(
            UserFactory::new()->make([
                $this->getUnassociatedUserData()
            ])
        );
        // Verifico che sia stato creato correttamente
        $createdUser = User::whereEmail($this->unassociatedUser->email)->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals($this->unassociatedUser->id, $createdUser->id);
    }

    public function testUnassociatedUserCannotVisitBackoffice()
    {
        $this->be($this->unassociatedUser)->assertAuthenticated();

        $this->call(
            'GET',
            route('dashboard.index')
        )->assertStatus(Response::HTTP_FORBIDDEN);

        $this->call(
            'GET',
            route('reservations.index')
        )->assertStatus(Response::HTTP_FORBIDDEN);

        $this->call(
            'GET',
            route('reservations.edit', ['reservation' => 1])
        )->assertStatus(Response::HTTP_FORBIDDEN);

        $this->call(
            'GET',
            route('reservations.create', ['reservation' => 1])
        )->assertStatus(Response::HTTP_FORBIDDEN);

        $this->call(
            'POST',
            route('reservations.poll')
        )->assertStatus(Response::HTTP_FORBIDDEN);

        $this->call(
            'POST',
            route('reservations.store', ['reservation' => 1])
        )->assertStatus(Response::HTTP_FORBIDDEN);

        $this->call(
            'POST',
            route('reservations.busytimes')
        )->assertStatus(Response::HTTP_FORBIDDEN);

        $this->call(
            'PUT',
            route('reservations.update', ['reservation' => 1])
        )->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testUnassociatedUserCannotLoginByApi()
    {
        $this->withHeaders([
            'Accept' => 'application/json'
        ])->postJson(
            '/api/login',
            [
                'email' => $this->unassociatedUser->email,
                'password' => 'test'
            ]
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testUnassociatedUserWithValidTokenCannotCallApi()
    {
        $this->clearTokens($this->unassociatedUser);
        $token = $this->unassociatedUser->createToken('app-token', [$this->unassociatedUser->email]);

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
            "/api/get-last-reservation-by-patient-email/{$this->unassociatedUser->email}"
        )->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ])->postJson(
            '/api/reservation',
            []
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testUnassociatedUserWithInvalidTokenCannotCallApi()
    {
        $this->clearTokens($this->unassociatedUser);

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
            "/api/get-last-reservation-by-patient-email/{$this->unassociatedUser->email}"
        )->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . 'invalid_token'
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
