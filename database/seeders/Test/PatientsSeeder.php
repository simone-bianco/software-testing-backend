<?php

namespace Database\Seeders\Test;

use App\Models\Account;
use App\Models\Patient;
use App\Models\Structure;
use App\Repositories\PatientRepository;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Validation\ValidationException;
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;
use Throwable;

class PatientsSeeder extends Seeder
{
    protected PatientRepository $patientRepository;

    /**
     * PatientsSeeder constructor.
     * @param  PatientRepository  $patientRepository
     */
    public function __construct(
        PatientRepository $patientRepository
    ) {
        $this->patientRepository = $patientRepository;
    }

    /**
     * @param  int  $numberOfPatientsPerStructure
     * @throws Throwable
     * @throws ValidationException
     */
    public function run(int $numberOfPatientsPerStructure = 3)
    {
        $structures = Structure::all();
        foreach ($structures as $structureIndex => $structure) {
            for ($patientIndex = 0; $patientIndex < $numberOfPatientsPerStructure; $patientIndex++) {
                $firstName = 'paziente';
                $lastName = 'test';
                $email = sprintf('%s_%s_%s%s@email.it', $firstName, $lastName, $structureIndex, $patientIndex);

                $genderValue = 1;
                $dob = Carbon::today()->subYears(20 - $structureIndex)
                    ->subDays($patientIndex);
                $birthPlace = 'napoli';
                $fiscalCodeGender = 'M';
                $address = sprintf('via di test %s%s', $structureIndex, $patientIndex);

                $accountAttributes = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'date_of_birth' => $dob,
                    'address' => $address,
                    'gender' => $genderValue,
                    'fiscal_code' => CodiceFiscale::generate(
                            $firstName,
                            $lastName,
                            $dob->format('Y-m-d'),
                            $birthPlace,
                            $fiscalCodeGender
                        ),
                    'city' => $birthPlace,
                    'cap' => '55666',
                    'mobile_phone' => sprintf('3339996%s%s', $structureIndex, $patientIndex)
                ];

                $patientAttributes = [
                    'heart_disease' => false,
                    'allergy' => false,
                    'immunosuppression' => false,
                    'anticoagulants' => false,
                    'covid' => false,
                    'account_id' => false,
                ];

                $this->patientRepository->saveOrCreate(
                    Patient::factory()->make($patientAttributes),
                    Account::factory()->make($accountAttributes),
                    $email,
                    'test'
                );
            }
        }
    }
}
