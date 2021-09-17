<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Session;

class TwoFA
{
    /**
     * @param  Request  $request
     * @param  Closure  $next
     * @return RedirectResponse|mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var Session $user */
        $session = $request->session();

        if (!$session->exists('2fa') || !$session->get('2fa')) {
            return redirect()->route('2fa.authenticate');
        }

        return $next($request);
    }
}
