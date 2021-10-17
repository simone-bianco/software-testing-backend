<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\ResponsibleRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use PragmaRX\Google2FAQRCode\Google2FA;
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

    public function authenticate(Request $request)
    {
        return Inertia::render("Auth/Authenticate2FA");
    }

    public function completeLogin(Request $request)
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
