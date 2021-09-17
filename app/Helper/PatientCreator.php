<?php

namespace App\Helper;

use App\Helper\Generator\AccountGenerator;
use App\Helper\Generator\EmailGenerator;
use App\Helper\Generator\PatientGenerator;
use App\Models\Account;
use App\Models\Patient;
use App\Repositories\PatientRepository;
use Arr;
use Exception;
use Illuminate\Validation\ValidationException;
use Log;
use Throwable;

class PatientCreator
{
    protected AccountGenerator $accountGenerator;
    protected PatientGenerator $patientGenerator;
    protected PatientRepository $patientRepository;
    protected EmailGenerator $emailGenerator;

    /**
     * PatientsSeeder constructor.
     * @param  AccountGenerator  $accountGenerator
     * @param  PatientGenerator  $patientGenerator
     * @param  EmailGenerator  $emailGenerator
     * @param  PatientRepository  $patientRepository
     */
    public function __construct(
        AccountGenerator $accountGenerator,
        PatientGenerator $patientGenerator,
        EmailGenerator $emailGenerator,
        PatientRepository $patientRepository
    ) {
        $this->accountGenerator = $accountGenerator;
        $this->patientGenerator = $patientGenerator;
        $this->emailGenerator = $emailGenerator;
        $this->patientRepository = $patientRepository;
    }

    /**
     * @return Patient
     * @throws ValidationException
     * @throws Throwable
     */
    public function execute(): Patient
    {
        try {
            $accountAttributes = $this->accountGenerator->generateAccountAttributes();
            $patientAttributes = $this->patientGenerator->generatePatientAttributes();

            return $this->patientRepository->saveOrCreate(
                Patient::factory()->make($patientAttributes),
                Account::factory()->make($accountAttributes),
                $this->emailGenerator->generateEmail(
                    Arr::get($accountAttributes, 'first_name'),
                    Arr::get($accountAttributes, 'last_name'),
                ),
                'test'
            );
        } catch (Exception $exception) {
            Log::error(sprintf("PatientSeeder Exception: %s", $exception->getMessage()));
            throw $exception;
        }
    }

    /**
     * @param  int  $qty
     * @return Patient[]
     * @throws ValidationException
     * @throws Throwable
     */
    public function make(int $qty): array
    {
        $patients = [];
        for ($i = 0; $i < $qty; $i++) {
            $patients[] = $this->execute();
        }

        return $patients;
    }
}
