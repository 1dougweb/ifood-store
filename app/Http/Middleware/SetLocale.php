<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = 'pt'; // Default

        // Get locale from authenticated user preference
        if ($request->user() && $request->user()->language) {
            $locale = $request->user()->language;
        } elseif ($request->hasHeader('Accept-Language')) {
            // Fallback to browser language
            $locale = substr($request->getPreferredLanguage(), 0, 2);
        }

        // Validate locale (only allow pt or en)
        if (!in_array($locale, ['pt', 'en'])) {
            $locale = 'pt';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
