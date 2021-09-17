<?php

namespace App\Repositories;

use App\Exceptions\PatientNotFoundException;
use App\Models\Account;
use App\Models\Patient;
use App\Models\User;
use App\Validators\PatientValidator;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Log;
use Throwable;

/**
 * Class PatientRepository
 * @package App\Repositories
 */
class PatientRepository
{
    private PatientValidator $patientValidator;
    private AccountRepository $accountRepository;

    public function __construct(
        PatientValidator $patientValidator,
        AccountRepository $accountRepository
    ) {
        $this->patientValidator = $patientValidator;
        $this->accountRepository = $accountRepository;
    }

    /**
     * @param  string  $email
     * @return Patient
     * @throws PatientNotFoundException
     */
    public function get(string $email): Patient
    {
        try {
            $user = User::whereEmail($email)->firstOrFail();
            if (!$user->patient()->first()) {
                throw new PatientNotFoundException("L'utente non è un paziente");
            }
            $patient = $user->account->patient;
        } catch (ModelNotFoundException $e) {
            throw new PatientNotFoundException($e->getMessage());
        }

        return $patient;
    }

    /**
     * @return Patient[]|Collection
     */
    public function all()
    {
        return Patient::all();
    }

    /**
     * @param  Patient  $patient
     * @param  Account|null  $account
     * @param  string|null  $email
     * @param  string|null  $password
     * @return Patient
     * @throws Throwable
     * @throws ValidationException
     */
    public function saveOrCreate(
        Patient $patient,
        ?Account $account = null,
        ?string $email = null,
        ?string $password = null

    ): Patient {
        try {
            DB::beginTransaction();

            if (!$account) {
                if ($patient->id) {
                    $account = $patient->account;
                }
            } else {
                $account->id = $account->id ?? $patient->account_id;
                $account->user_id = $patient->account_id ?? null;
            }

            if ($patient->account()->exists()) {
                $email = $email ?? $patient->account->user->email;
            }

            $account = $this->accountRepository->saveOrCreate($account, $email, $password);
            $patient->account_id = $account->id;

            $newPatient = $this->assignAndSave(
                $patient->id ? Patient::findOrFail($patient->id) : Patient::factory()->newModel(),
                $patient
            );

            DB::commit();

            return $newPatient;
        } catch (ValidationException $validationException) {
            DB::rollBack();
            Log::error("Patient saveOrCreate Validation:\n" . $validationException->getMessage());
            Log::error(print_r($validationException->errors(), true));
            throw $validationException;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error("Patient saveOrCreate:\n" . $exception->getMessage());
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @param  Patient  $patient
     * @param  Account|null  $account
     * @param  string|null  $email
     * @param  string|null  $password
     * @return Patient
     * @throws Throwable
     * @throws ValidationException
     */
    public function save(
        Patient $patient,
        ?Account $account = null,
        ?string $email = null,
        ?string $password = null
    ): Patient {
        try {
            DB::beginTransaction();

            $oldPatient = Patient::findOrFail($patient->id);

            if (!$account) {
                $account = $oldPatient->account;
            }

            $account->id = $patient->account_id;
            $account->user_id = $patient->account->user->id;

            $this->accountRepository->save($account, $email, $password);

            $newPatient = $this->assignAndSave($oldPatient, $patient);

            DB::commit();

            return $newPatient;
        } catch (ValidationException $validationException) {
            DB::rollBack();
            Log::error("Patient save Validation:\n" . $validationException->getMessage());
            Log::error(print_r($validationException->errors(), true));
            throw $validationException;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error("Patient save:\n" . $exception->getMessage());
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @param  Patient  $newPatient
     * @param  Patient  $patient
     * @return Patient
     * @throws PatientNotFoundException
     * @throws ValidationException
     */
    private function assignAndSave(Patient $newPatient, Patient $patient): Patient
    {
        $newPatient->heart_disease = $patient->heart_disease;
        $newPatient->allergy = $patient->allergy;
        $newPatient->immunosuppression = $patient->immunosuppression;
        $newPatient->anticoagulants = $patient->anticoagulants;
        $newPatient->covid = $patient->covid;
        $newPatient->account_id = $patient->account_id;

        $this->patientValidator->validateData($newPatient->toArray(), ['patient' => $newPatient]);
        $newPatient->save();

        return $this->get($newPatient->email);
    }
}
