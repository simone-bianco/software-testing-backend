<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Session;

class FirstLogin
{
    /**
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->responsible()->exists() || $user->director()->exists()) {
            if ($user->first_login) {
                return redirect()->route('2fa.register');
            }
        } else {
            Session::flush();
            throw new AuthorizationException('Utente non autorizzato');
        }

        return $next($request);
    }
}
