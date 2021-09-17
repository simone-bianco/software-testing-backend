<?php

namespace Database\Seeders;

use App\Helper\Generator\AccountGenerator;
use App\Models\Structure;
use App\Repositories\AccountRepository;
use App\Repositories\PatientRepository;
use App\Repositories\ResponsibleRepository;
use App\Repositories\StructureRepository;
use Database\Factories\AccountFactory;
use Database\Factories\PatientFactory;
use Database\Factories\ResponsibleFactory;
use Faker\Provider\it_IT\Person;
use Illuminate\Database\Seeder;
use Illuminate\Validation\ValidationException;
use Log;
use Throwable;

class AccountSeeder extends Seeder
{
    protected Person $person;
    protected AccountRepository $accountRepository;
    protected AccountGenerator $accountGenerator;
    protected ResponsibleRepository $responsibleRepository;
    protected StructureRepository $structureRepository;
    protected PatientRepository $patientRepository;

    /**
     * AccountSeeder constructor.
     * @param  Person  $person
     * @param  AccountRepository  $accountRepository
     * @param  AccountGenerator  $accountGenerator
     * @param  ResponsibleRepository  $responsibleRepository
     * @param  StructureRepository  $structureRepository
     * @param  PatientRepository  $patientRepository
     */
    public function __construct(
        Person $person,
        AccountRepository $accountRepository,
        AccountGenerator $accountGenerator,
        ResponsibleRepository $responsibleRepository,
        StructureRepository $structureRepository,
        PatientRepository $patientRepository
    ) {
        $this->person = $person;
        $this->accountRepository = $accountRepository;
        $this->accountGenerator = $accountGenerator;
        $this->responsibleRepository = $responsibleRepository;
        $this->structureRepository = $structureRepository;
        $this->patientRepository = $patientRepository;
    }

    public function run()
    {
        try {
            $structure = Structure::firstOrFail();

            $operators = [
                ['first_name' => 'admin', 'email' => 'admin@email.it'],
                ['first_name' => 'Gianfranco', 'last_name' => 'Paduano', 'email' => 'operator@email.it'],
            ];

            $patients = [
                ['first_name' => 'patient', 'email' => 'patient@email.it'],
                ['first_name' => 'patient', 'email' => 'patient.2@email.it'],
            ];

            foreach ($operators as $operator) {
                $this->responsibleRepository->saveOrCreate(
                    ResponsibleFactory::new()->make(['structure_id' => $structure->id]),
                    AccountFactory::new()->make(
                        $this->accountGenerator->generateAccountAttributes(
                            [
                                'first_name' => $operator['first_name'],
                                'last_name' => 'test'
                            ]
                        )
                    ),
                    $operator['email'],
                    'test'
                );
            }

            foreach ($patients as $patient) {
                $this->patientRepository->saveOrCreate(
                    PatientFactory::new()->make([
                        'heart_disease' => false,
                        'allergy' => false,
                        'immunosuppression' => false,
                        'anticoagulants' => false,
                        'covid' => false,
                    ]),
                    AccountFactory::new()->make(
                        $this->accountGenerator->generateAccountAttributes(
                            [
                                'first_name' => $patient['first_name'],
                                'last_name' => 'test'
                            ]
                        )
                    ),
                    $patient['email'],
                    'test'
                );
            }
        } catch (ValidationException $validationException) {
            var_dump("AccountSeeder ValidationException: " . $validationException->getMessage());
            Log::error("AccountSeeder: " . $validationException->getMessage());
            Log::error(print_r($validationException->errorBag, true));
        } catch (Throwable $exception) {
            var_dump("AccountSeeder Exception: " . $exception->getMessage());
            Log::error("AccountSeeder: " . $exception->getMessage());
            Log::error(print_r($exception->getTraceAsString(), true));
        }
    }
}
