<?php

namespace App\Http\Controllers;

use App\Helper\ResponsibleCreator;
use App\Models\Responsible;
use App\Models\Structure;
use App\Models\User;
use App\Repositories\ReservationRepository;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Log;
use PragmaRX\Google2FA\Google2FA;
use Throwable;

class TestApiController extends Controller
{
    protected ReservationRepository $reservationRepository;
    protected ResponsibleCreator $responsibleCreator;

    /**
     * @param  ReservationRepository  $reservationRepository
     * @param  ResponsibleCreator  $responsibleCreator
     */
    public function __construct(
        ReservationRepository $reservationRepository,
        ResponsibleCreator $responsibleCreator
    ) {
        $this->reservationRepository = $reservationRepository;
        $this->responsibleCreator = $responsibleCreator;
    }

    /**
     * @param  string  $secret
     * @return JsonResponse
     */
    public function getTwoFACodeBySecret(string $secret): JsonResponse
    {
        try {
            Log::channel('daily')->info('getTwoFACodeBySecret begins');
            $this->tokenValidation(request()->header('token'));
            $google2fa = new Google2FA();
            Log::channel('daily')->info("getTwoFACodeBySecret secret: $secret");
            Log::channel('daily')->info('getTwoFACodeBySecret ends');
            $currentSeconds = (int)Carbon::now()->format('s') % 30;
            if ($currentSeconds > 24 && $currentSeconds < 31) {
                sleep(30 - $currentSeconds + 1);
            }
            return response()->json(['otp' => $google2fa->getCurrentOtp($secret)]);
        } catch (Exception $exception) {
            Log::channel('daily')->info("getTwoFACodeBySecret exception: {$exception->getMessage()}");
            return response()->json($exception->getMessage(), 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function firstLoginTestSetup(): JsonResponse
    {
        $uniqueId = Str::random();
        try {
            $this->tokenValidation(request()->header('token'));
            Log::channel('daily')->info("firstLoginTestSetup begins - $uniqueId");
            $responsible = $this->responsibleCreator->createResponsible(Structure::firstOrFail(), $uniqueId);
            Log::channel('daily')->info("firstLoginTestSetup ends - $uniqueId");
            return response()->json([
                'email' => $responsible->email,
                'password' => 'test',
                'id' => $uniqueId
            ]);
        } catch (Throwable $exception) {
            Log::channel('daily')->info("firstLoginTestSetup exception - $uniqueId: {$exception->getMessage()}");
            return response()->json($exception->getMessage(), 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function firstLoginTestTeardown(): JsonResponse
    {
        $uniqueId = request()->post('id');
        try {
            $this->tokenValidation(request()->header('token'));
            Log::channel('daily')->info(print_r(request()->toArray(), true));
            Log::channel('daily')->info("firstLoginTestTeardown begins - $uniqueId");
            if (!$uniqueId) {
                throw new Exception("ID non presente");
            }
            Log::channel('daily')->info("firstLoginTestTeardown begins - $uniqueId");
            $user = User::where('email', 'LIKE', "%$uniqueId%")->firstOrFail();
            $account = $user->account;
            $responsible = $user->responsible;
            $responsible->delete();
            $account->delete();
            $user->delete();
            Log::channel('daily')->info("firstLoginTestTeardown ends - $uniqueId");
            return response()->json(['message' => 'ok']);
        } catch (Throwable $exception) {
            Log::channel('daily')->info("firstLoginTestTeardown exception - $uniqueId: {$exception->getMessage()}");
            return response()->json($exception->getMessage(), 500);
        }
    }

    /**
     * @param  string  $token
     * @throws AuthorizationException
     */
    protected function tokenValidation(string $token)
    {
        if (!in_array($token, config('test.tokens_whitelist'))) {
            throw new AuthorizationException("Token di autorizzazione non valido");
        }
    }
}
