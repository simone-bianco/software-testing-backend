<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Patient;
use App\Models\User;
use App\Repositories\PatientRepository;
use Carbon\Carbon;
use DB;
use Exception;
use Faker\Generator;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Log;
use PDOException;
use SQLiteException;
use Throwable;

class TestRegistration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:registration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    protected Generator $faker;
    protected PatientRepository $patientRepository;

    protected array $rotatingFields = ['first_name', 'last_name', 'date_of_birth', 'address', 'gender', 'fiscal_code', 'city',
        'cap', 'mobile_phone'];
    protected array $randomFunctions = ['randomAscii', 'password', 'address', 'email', 'boolean', 'name', 'date', 'randomFloat',
        'randomHtml', 'text', 'fileExtension', 'random'];

    /**
     * Create a new command instance.
     *
     * @param  Generator  $faker
     * @param  PatientRepository  $patientRepository
     */
    public function __construct(
        Generator $faker,
        PatientRepository $patientRepository
    ) {
        parent::__construct();
        $this->faker = $faker;
        $this->patientRepository = $patientRepository;
    }

    /**
     * @throws Exception
     */
    protected function trueRandom()
    {
        $random = random_int(0, 21);
        if ($random <= 10) return $this->faker->{$this->randomFunctions[$random]};
        switch ($random) {
            case 11:
                return [];
            case 12:
                return \Str::random(random_int(1, 800));
            case 13:
                return null;
            case 14:
                return PHP_FLOAT_MAX;
            case 15:
                return PHP_FLOAT_MIN;
            case 16:
                return PHP_FLOAT_MAX + 10;
            case 17:
                return PHP_FLOAT_MIN - 10;
            case 18:
                return PHP_INT_MAX;
            case 19:
                return PHP_INT_MAX + 10;
            case 20:
                return PHP_INT_MIN;
            case 21:
                return PHP_INT_MIN - 10;
            default:
                return null;
        }
    }

    protected function getValidAccountAttributes(): array
    {
        return [
            'first_name' => 'nome',
            'last_name' => 'cognome',
            'date_of_birth' => Carbon::today()->subYears(20),
            'address' => $this->faker->address,
            'gender' => 0,
            'fiscal_code' => 'BBBSSS87P09F777G',
            'city' => 'napoli',
            'cap' => 80111,
            'mobile_phone' => '3334542010',
        ];
    }

    protected function getValidUserAttributes(): array
    {
        return [
            'heart_disease' => 1,
            'allergy' => 1,
            'immunosuppression' => 1,
            'anticoagulants' => 1,
            'covid' => 1,
        ];
    }

    protected function deleteIfExists()
    {
        $account = Account::whereFiscalCode('BBBSSS87P09F777G')->first();
        if ($account) {
            $patient = Patient::whereId($account->patient->id)->first();
            if ($account->patient) {
                $patient->delete();
            }
            $user = User::whereId($account->id)->first();
            if ($user) {
                $user->delete();
            }
            $account->delete();
        }
    }

    /**
     * @throws Throwable
     */
    public function handle()
    {
        $this->deleteIfExists();
        $email = "testemail@email.it";
        $patientData = $this->getValidUserAttributes();
        $accountData = $this->getValidAccountAttributes();
        foreach ($this->rotatingFields as $rotatingField) {
            for ($i = 0; $i < 120; $i++) {
                try {
                    DB::beginTransaction();
                    $this->patientRepository->saveOrCreate(
                        Patient::factory()->make($patientData),
                        Account::factory()->make(array_merge($accountData, [$rotatingField => $this->trueRandom()])),
                        $email,
                        'test',
                    );
                    DB::rollBack();
                } catch (ValidationException $validationException) {
                    DB::rollBack();
                } catch (PDOException | QueryException | SQLiteException $exception) {
                    DB::rollBack();
                    $message = $exception->getMessage();
                    $this->logException($email, $message);
                } catch (\Throwable $exception) {
                    DB::rollBack();
                    $message = $exception->getMessage();
                    if (str_contains($message, "SQLSTATE")) {
                        $this->logException($email, $message);
                    }
                }
            }
        }
    }

    protected function logException($email, $message)
    {
        Log::channel('daily')->error("Uncaught Query exception!");
        Log::channel('daily')->error('$email: ' . $email);
        Log::channel('daily')->error('$password: ' . 'test');
        Log::channel('daily')->error($message);
        Log::channel('daily')->error("#########################################################");
    }
}
