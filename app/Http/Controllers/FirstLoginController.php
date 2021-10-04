<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\ResponsibleRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FAQRCode\Exceptions\MissingQrCodeServiceException;
use PragmaRX\Google2FAQRCode\Google2FA;
use Request;
use Storage;
use Str;
use Validator;

class FirstLoginController extends Controller
{
    protected ResponsibleRepository $responsibleRepository;
    protected UserRepository $userRepository;

    /**
     * FirstLoginController constructor.
     * @param  ResponsibleRepository  $responsibleRepository
     * @param  UserRepository  $userRepository
     */
    public function __construct(
        ResponsibleRepository $responsibleRepository,
        UserRepository $userRepository
    ) {
        $this->responsibleRepository = $responsibleRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws MissingQrCodeServiceException
     * @throws SecretKeyTooShortException
     */
    public function register(\Illuminate\Http\Request $request)
    {
        $user = $request->user();

        $google2fa = (new Google2FA());
        $secret = $google2fa->generateSecretKey(64);
        $user->google2fa_secret = $secret;
        $user->save();
        $imageQR = $google2fa->getQRCodeInline(
            'Piattaforma Vaccini SAD',
            $user->email,
            $user->google2fa_secret
        );

        return Inertia::render("Auth/RegisterQRCode", ["qr" => $imageQR, 'secret' => $secret]);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     * @throws ValidationException
     */
    public function completeRegistration(\Illuminate\Http\Request $request)
    {
        $google2fa = (new Google2FA());

        $secret = $request->get('secret');
        $currentOtp = $google2fa->getCurrentOtp($secret);
        $otp = $request->get('code');

        Validator::validate(
            ['otp' => $otp],
            ['otp' => ['required', 'regex:/(^[0-9]{6})/', Rule::in([$currentOtp])]]
        );

        /** @var User $user */
        $user = $request->user();
        $user->setGoogle2faSecretAttribute($secret);
        $user->first_login = false;
        $user->save();

        return Redirect::route('dashboard.index')->with(['success' => 'Registrazione completata con successo']);
    }
}
