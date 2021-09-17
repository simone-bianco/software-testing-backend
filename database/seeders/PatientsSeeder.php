<?php

namespace Database\Seeders;

use App\Helper\Generator\AccountGenerator;
use App\Helper\Generator\EmailGenerator;
use App\Helper\Generator\PatientGenerator;
use App\Helper\PatientCreator;
use App\Models\Account;
use App\Models\Patient;
use App\Repositories\PatientRepository;
use Arr;
use Exception;
use Illuminate\Database\Seeder;
use Log;
use Throwable;

class PatientsSeeder extends Seeder
{
    protected PatientCreator $patientCreator;

    /**
     * PatientsSeeder constructor.
     * @param  PatientCreator  $patientCreator
     */
    public function __construct(
        PatientCreator $patientCreator
    ) {
        $this->patientCreator = $patientCreator;
    }

    /**
     * @param  int  $numberOfPatients
     * @throws Throwable
     * @throws \Illuminate\Validation\ValidationException
     */
    public function run(int $numberOfPatients = 20)
    {
        for ($i = 0; $i < $numberOfPatients; $i++) {
            $this->patientCreator->execute();
        }
    }
}
