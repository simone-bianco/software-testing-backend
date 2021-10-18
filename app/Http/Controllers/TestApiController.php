<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\ReservationRepository;
use Arr;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Log;
use PragmaRX\Google2FA\Google2FA;

class TestApiController extends Controller
{
    protected ReservationRepository $reservationRepository;

    /**
     * @param  ReservationRepository  $reservationRepository
     */
    public function __construct(
        ReservationRepository $reservationRepository
    ) {
        $this->reservationRepository = $reservationRepository;
    }

    /**
     * @param  string  $secret
     * @return JsonResponse
     */
    public function getTwoFACodeBySecret(string $secret): JsonResponse
    {
        try {
            Log::channel('api_test')->info('getTwoFACodeBySecret begins');
            $this->isAllowed(request()->header('token'));
            $google2fa = new Google2FA();
            Log::channel('api_test')->info("getTwoFACodeBySecret secret: $secret");
            Log::channel('api_test')->info('getTwoFACodeBySecret ends');
            return response()->json(['otp' => $google2fa->getCurrentOtp($secret)]);
        } catch (Exception $exception) {
            Log::channel('api_test')->info("getTwoFACodeBySecret exception: {$exception->getMessage()}");
            return response()->json($exception->getMessage(), 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function firstLoginTestSetup(): JsonResponse
    {
        try {
            Log::channel('api_test')->info('firstLoginTestSetup begins');
            $responsibleData = config('test.responsible');
            $responsibleEmail = Arr::get($responsibleData, 'email');
            $responsible = User::where('email', '=', $responsibleEmail)
                ->firstOrFail()
                ->responsible;
            $user = $responsible->account->user;
            $user->first_login = true;
            $user->setGoogle2faSecretAttribute(null);
            $user->save();
            $this->isAllowed(request()->header('token'));
            Log::channel('api_test')->info('firstLoginTestSetup ends');
            return response()->json($responsibleData);
        } catch (Exception $exception) {
            Log::channel('api_test')->info("firstLoginTestSetup exception: {$exception->getMessage()}");
            return response()->json($exception->getMessage(), 500);
        }
    }

    /**
     * @param  string  $token
     * @throws AuthorizationException
     */
    protected function isAllowed(string $token)
    {
        if (!in_array($token, config('test.tokens_whitelist'))) {
            throw new AuthorizationException("Token di autorizzazione non valido");
        }
    }
}
