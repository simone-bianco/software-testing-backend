<?php

namespace Tests\Unit;

use App\Helper\Generator\AccountGenerator;
use App\Repositories\PatientRepository;
use Artisan;
use Database\Factories\AccountFactory;
use Database\Factories\PatientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Throwable;

class PatientRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected PatientRepository $patientRepository;
    protected AccountGenerator $accountGenerator;

    public function setUp() : void {
        parent::setUp();
        $this->patientRepository = $this->app->make(PatientRepository::class);
        $this->accountGenerator = $this->app->make(AccountGenerator::class);
        Artisan::call('migrate:refresh');

    }

    public function testCreateSuccess()
    {
        $newPatient = $this->patientRepository->saveOrCreate(
            PatientFactory::new()->make($this->getPatientAttributes()),
            AccountFactory::new()->make(
                $this->accountGenerator->generateAccountAttributes($this->getAccountAttributes())
            ),
            'test@test.it',
            'test'
        );

        $this->assertEquals('test@test.it', $newPatient->account->user->email);
        $this->assertEquals('test_first_name', $newPatient->account->first_name);
        $this->assertEquals('test_last_name', $newPatient->account->last_name);
    }

    /**
     * @throws ValidationException
     * @throws Throwable
     */
    public function testSaveOrCreateUpdateSuccess()
    {
        $newPatient = $this->patientRepository->saveOrCreate(
            PatientFactory::new()->make($this->getPatientAttributes()),
            AccountFactory::new()->make(
                $this->accountGenerator->generateAccountAttributes($this->getAccountAttributes())
            ),
            'test@test.it',
            'test'
        );
        $newAccount = $newPatient->account;
        $newUser = $newPatient->account->user;
        $updatedPatient = $this->patientRepository->saveOrCreate(
            $newPatient,
            AccountFactory::new()->make(
                $this->accountGenerator->generateAccountAttributes(
                    [
                        'first_name' => 'updated_first_name',
                        'last_name' => 'updated_last_name'
                    ]
                )),
            'updated_email@email.it',
            'test'
        );

        $this->assertEquals($updatedPatient->id, $newPatient->id);
        $this->assertEquals($updatedPatient->account->id, $newAccount->id);
        $this->assertEquals($updatedPatient->account->user->id, $newUser->id);
        $this->assertEquals('updated_first_name', $updatedPatient->account->first_name);
        $this->assertEquals('updated_last_name', $updatedPatient->account->last_name);
        $this->assertEquals('updated_email@email.it', $updatedPatient->account->user->email);

        $updatedAccount = $updatedPatient->account;
        $updatedAccount->first_name = 'updated_first_name_2';
        $updatedPatient = $this->patientRepository->saveOrCreate($updatedPatient, $updatedAccount);

        $this->assertEquals('updated_first_name_2', $updatedPatient->account->first_name);
        $this->assertEquals('updated_email@email.it', $updatedPatient->account->user->email);
    }

    /**
     * @throws Throwable
     * @throws ValidationException
     */
    public function testSaveSuccess()
    {
        $newPatient = $this->patientRepository->saveOrCreate(
            PatientFactory::new()->make($this->getPatientAttributes()),
            AccountFactory::new()->make(
                $this->accountGenerator->generateAccountAttributes($this->getAccountAttributes())
            ),
            'test@test.it',
            'test'
        );

        $updatedPatient = $this->patientRepository->save(
            $newPatient,
            AccountFactory::new()->make(
                $this->accountGenerator->generateAccountAttributes(
                    [
                        'first_name' => 'updated_first_name',
                        'last_name' => 'updated_last_name'
                    ]
                )),
            'updated_email@email.it',
            'test'
        );

        $this->assertEquals($updatedPatient->id, $newPatient->id);
        $this->assertEquals($updatedPatient->account->id, $newPatient->account->id);
        $this->assertEquals($updatedPatient->account->user->id, $newPatient->account->user->id);
        $this->assertEquals('updated_first_name', $updatedPatient->account->first_name);
        $this->assertEquals('updated_last_name', $updatedPatient->account->last_name);
        $this->assertEquals('updated_email@email.it', $updatedPatient->account->user->email);

        $updatedPatient = $this->patientRepository->save(
            $updatedPatient
        );

        $this->assertEquals($updatedPatient->id, $newPatient->id);
        $this->assertEquals($updatedPatient->account->id, $newPatient->account->id);
        $this->assertEquals($updatedPatient->account->user->id, $newPatient->account->user->id);
        $this->assertEquals('updated_first_name', $updatedPatient->account->first_name);
        $this->assertEquals('updated_last_name', $updatedPatient->account->last_name);
        $this->assertEquals('updated_email@email.it', $updatedPatient->account->user->email);
    }

    protected function getPatientAttributes(array $overwrite = []): array
    {
        return array_merge([
            'heart_disease' => false,
            'allergy' => false,
            'immunosuppression' => false,
            'anticoagulants' => false,
            'covid' => false,
        ], $overwrite);
    }

    protected function getAccountAttributes(array $overwrite = []): array
    {
        return array_merge([
            'first_name' => 'test_first_name',
            'last_name' => 'test_last_name',
        ], $overwrite);
    }
}
