<?php

namespace Database\Seeders\Test;

use App\Models\Account;
use App\Models\Structure;
use App\Repositories\ResponsibleRepository;
use Carbon\Carbon;
use Database\Factories\ResponsibleFactory;
use Illuminate\Database\Seeder;
use PragmaRX\Google2FAQRCode\Google2FA;
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;
use Throwable;

class ResponsibleSeeder extends Seeder
{
    protected ResponsibleRepository $responsibleRepository;

    /**
     * ResponsiblesSeeder constructor.
     * @param  ResponsibleRepository  $responsibleRepository
     */
    public function __construct(
        ResponsibleRepository $responsibleRepository
    ) {
        $this->responsibleRepository = $responsibleRepository;
    }

    /**
     * @throws Throwable
     */
    public function run(bool $twoFAComplete = false)
    {
        $structures = Structure::all();
        foreach ($structures as $index => $structure) {
            $firstName = "responsabile";
            $lastName = "test";
            $email = sprintf('%s_%s_%s@email.it', $firstName, $lastName, $index);

            $genderValue = 1;
            $dob = Carbon::today()->subYears(20 + $index);
            $birthPlace = "Napoli";
            $fiscalCodeGender = 'M';
            $address = "Via fittizia 57";
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
                'cap' => "80137",
                'mobile_phone' => "333666999" . $index,
            ];
            /** @var Account $account */
            $account = Account::factory()->make($accountAttributes);

            $responsible = $this->responsibleRepository->saveOrCreate(
                ResponsibleFactory::new()->make(['structure_id' => $structure->id]),
                $account,
                $email,
                'test'
            );

            if (!$twoFAComplete) {
                continue;
            }

            $user = $responsible->account->user;
            $google2fa = (new Google2FA());
            $secret = $google2fa->generateSecretKey(64);
            $user->setGoogle2faSecretAttribute($secret);
            $user->first_login = false;
            $user->save();
        }
    }
}
