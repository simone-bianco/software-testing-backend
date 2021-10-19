<?php

namespace App\Helper;

use App\Models\Account;
use App\Models\Responsible;
use App\Models\Structure;
use App\Repositories\ResponsibleRepository;
use Carbon\Carbon;
use Database\Factories\ResponsibleFactory;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FAQRCode\Google2FA;
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;
use Throwable;

class ResponsibleCreator
{
    protected ResponsibleRepository $responsibleRepository;

    /**
     * @param  ResponsibleRepository  $responsibleRepository
     */
    public function __construct(
        ResponsibleRepository $responsibleRepository
    ) {
        $this->responsibleRepository = $responsibleRepository;
    }

    /**
     * @param  Structure  $structure
     * @param  string  $uniqueId
     * @param  bool  $twoFAComplete
     * @return Responsible
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     * @throws Throwable
     * @throws ValidationException
     */
    public function createResponsible(Structure $structure, string $uniqueId, bool $twoFAComplete = false): Responsible
    {
        $firstName = "responsabile";
        $lastName = "test";
        $email = sprintf('%s@testim.test.it', $uniqueId);

        $genderValue = 1;
        $dob = Carbon::today()->subYears(20);
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
            'mobile_phone' => (string)(30000000 + random_int(0, 1000000)),
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
            return $responsible;
        }

        $user = $responsible->account->user;
        $google2fa = (new Google2FA());
        $secret = $google2fa->generateSecretKey(64);
        $user->setGoogle2faSecretAttribute($secret);
        $user->first_login = false;
        $user->save();

        return $responsible;
    }
}