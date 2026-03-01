<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Set application locale from session or config default.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('localhelp.locale.default'));
        $available = config('localhelp.locale.available', ['en']);

        if (in_array($locale, $available)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
