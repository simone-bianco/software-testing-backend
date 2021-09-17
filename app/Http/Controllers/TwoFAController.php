<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\ResponsibleRepository;
use App\Repositories\UserRepository;
use Auth;
use BaconQrCode\Common\Mode;
use BaconQrCode\Encoder\QrCode;
use Exception;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Support\Constants;
use PragmaRX\Google2FAQRCode\Exceptions\MissingQrCodeServiceException;
use PragmaRX\Google2FAQRCode\Google2FA;
use Request;
use Storage;
use Str;
use Validator;

class TwoFAController extends Controller
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

    public function authenticate(\Illuminate\Http\Request $request)
    {
        return Inertia::render("Auth/Authenticate2FA");
    }

    public function completeLogin(\Illuminate\Http\Request $request)
    {
        $google2fa = (new Google2FA());
        $otp = $request->get('code');

        /** @var User $user */
        $user = $request->user();
        $secret = $user->google2fa_secret;

        $currentOtp = $google2fa->getCurrentOtp($secret);

        Validator::validate(
            ['otp' => $otp],
            ['otp' => ['required', 'regex:/(^[0-9]{6})/', Rule::in([$currentOtp])]]
        );

        $request->session()->put('2fa', true);

        return Redirect::route('dashboard.index')->with(['success' => 'Login effettuato con successo']);
    }
}
