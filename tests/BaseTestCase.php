<?php

namespace Tests;

use App\Models\Account;
use Carbon\Carbon;
use Database\Factories\AccountFactory;
use Database\Factories\BatchFactory;
use Database\Factories\PatientFactory;
use Database\Factories\StockFactory;
use Database\Factories\StructureFactory;
use Database\Factories\UserFactory;
use Database\Factories\VaccineFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Fornisce un set di dati base inizializzati con le factory, quindi senza fare uso di seeder
 * e/o repository
 */
abstract class BaseTestCase extends TestCase
{
    use RefreshDatabase;

    public function setUp() : void {
        parent::setUp();
        $structure = StructureFactory::new()->create([
            'name' => 'test',
            'region' => 'campania',
            'address' => 'fake address 55',
            'capacity' => 24,
            'phone_number' => '333666333',
        ]);
        $user = UserFactory::new()->create([
            'email' => 'test@email.it',
            'name' => 'test user'
        ]);
        $account = AccountFactory::new()->create([
            'first_name' => 'test',
            'last_name' => 'user',
            'date_of_birth' => Carbon::now()->subYears(30),
            'gender' => Account::GENDER_MALE,
            'fiscal_code' => 'XXXYYY82F942G',
            'address' => 'fake address 11',
            'city' => 'napoli',
            'cap' => '10323',
            'mobile_phone' => '111222333',
            'user_id' => $user->id,
        ]);
        PatientFactory::new()->create([
            'account_id' => $account->id,
        ]);
        $vaccine = VaccineFactory::new()->create([
            "name" => "Pfizer",
            "vaccine_doses" => 2,
            "src" => "vaccine_images/pfizer.jpg",
            "lazy_src" => "vaccine_images/pfizer_lazy.jpg",
            "url" => "https://www.pfizer.com/"
        ]);
        $batch = BatchFactory::new()->create([
            'vaccine_id' => $vaccine->id
        ]);
        StockFactory::new()->create([
            'batch_id' => $batch->id,
            'structure_id' => $structure->id
        ]);
    }

    protected function assertPreConditions(): void
    {
        //Mi assicuro che la quantità di dati inserita corrisponda a quella aspettata
        $this->assertDatabaseCount('structures', 1);
        $this->assertDatabaseCount('patients', 1);
        $this->assertDatabaseCount('vaccines', 1);
        $this->assertDatabaseCount('batches', 1);
        $this->assertDatabaseCount('stocks', 1);
    }
}
