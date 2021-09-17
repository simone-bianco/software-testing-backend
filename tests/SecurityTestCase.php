<?php

namespace Tests;

use App\Helper\PatientCreator;
use App\Models\Reservation;
use App\Models\Responsible;
use App\Models\Structure;
use App\Models\User;
use App\Repositories\ReservationRepository;
use Artisan;
use Carbon\Carbon;
use Database\Seeders\TestDatabaseSeeder;
use Dotenv\Exception\ValidationException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Throwable;

abstract class SecurityTestCase extends TestCase
{
    use RefreshDatabase;

    protected TestDatabaseSeeder $testDatabaseSeeder;
    protected PatientCreator $patientCreator;
    protected ReservationRepository $reservationRepository;

    protected Structure $firstStructure;
    /** @var Structure[] $otherStructures */
    protected array $otherStructures;
    protected Responsible $unauthorizedResponsible;

    /**
     * @throws BindingResolutionException
     * @throws Throwable
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->testDatabaseSeeder = $this->app->make(TestDatabaseSeeder::class);
        $this->patientCreator = $this->app->make(PatientCreator::class);
        $this->reservationRepository = $this->app->make(ReservationRepository::class);
        Artisan::call('migrate:refresh');
        // Riempio il DB con i dati di test
        $this->testDatabaseSeeder->run();
        // Creo una reservation per ogni struttura
        $this->createReservations();
        // Verifico le precondizioni, cioè che entrambe le strutture abbiano una reservation
        $this->assertDatabaseCount('reservations', 2);
        $structures = Structure::all();
        foreach ($structures as $structure) {
            $reservation = $this->getFirstReservation($structure);
            $this->assertNotNull($reservation);
            $this->assertInstanceOf(Reservation::class, $reservation);
        }
        $this->firstStructure = $structures->first();
        $this->otherStructures = Structure::where('id', '!=', $this->firstStructure->id)->get()->all();
        $this->unauthorizedResponsible = $this->firstStructure->responsibles()->first();
        $unauthorizedResponsibleUser = $this->unauthorizedResponsible->account->user;
        $unauthorizedResponsibleUser->first_login = false;
        $unauthorizedResponsibleUser->save();
        $this->assertNotNull($this->unauthorizedResponsible);
        $this->assertInstanceOf(Responsible::class, $this->unauthorizedResponsible);
        $this->assertEquals($this->unauthorizedResponsible->structure->id, $this->firstStructure->id);
    }

    /**
     * @param  Structure  $structure
     * @return Reservation
     */
    protected function getFirstReservation(Structure $structure): Reservation
    {
        return $structure->stocks()
            ->with('reservations')
            ->get()
            ->pluck('reservations')
            ->filter(function ($reservation) {
                if ($reservation != [] && $reservation) {
                    return true;
                }
                return false;
            })->first()[0];
    }

    /**
     * @throws Throwable
     * @throws ValidationException
     */
    protected function createReservations()
    {
        $structures = Structure::all();
        foreach ($structures as $structure) {
            // Scelgo lo stock e mi assicuro che la quantità sia 1
            $stock = $structure->stocks()->firstOrFail();
            $stock->quantity = 1;
            $stock->saveOrFail();
            // Creo un paziente
            $patient = $this->patientCreator->execute();
            $reservation = Reservation::make([
                'date' => Carbon::now()->addDays(10),
                'patient_id' => $patient->id,
                'stock_id' => $stock->id
            ]);
            $this->reservationRepository->createAndStockDecrement($reservation);
        }
    }

    protected function clearTokens(User $user)
    {
        $user->tokens()->where('name', '=', 'app-token')->delete();
    }
}
