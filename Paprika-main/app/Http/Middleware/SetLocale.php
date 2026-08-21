<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next, ?string $locale = null): Response
    {
        // URL locale (from route prefix like /vi/, /en/, /el/)
        if ($locale) {
            App::setLocale($locale);
            $request->attributes->set('locale', $locale);
            return $next($request);
        }

        // Check session/cookie first (user preference)
        $storedLocale = ($request->hasSession() ? $request->session()->get('locale') : null) ?? $request->cookie('locale');
        if ($storedLocale && array_key_exists($storedLocale, config('locales.supported', []))) {
            App::setLocale($storedLocale);
            $request->attributes->set('locale', $storedLocale);
            return $next($request);
        }

        // Check browser Accept-Language header
        $browserLocale = $this->getBrowserLocale($request);
        if ($browserLocale) {
            App::setLocale($browserLocale);
            $request->attributes->set('locale', $browserLocale);
            return $next($request);
        }

        // Use admin setting as default (from database)
        $defaultLocale = setting('default_locale', config('locales.default', 'vi'));
        if (! array_key_exists($defaultLocale, config('locales.supported', []))) {
            $defaultLocale = config('locales.default', 'vi');
        }

        App::setLocale($defaultLocale);
        $request->attributes->set('locale', $defaultLocale);

        return $next($request);
    }

    private function getBrowserLocale(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');
        if (! $acceptLanguage) {
            return null;
        }

        $languages = [];
        foreach (explode(',', $acceptLanguage) as $part) {
            $part = trim($part);
            if (str_contains($part, ';')) {
                [$lang, $q] = explode(';', $part, 2);
                $q = floatval(str_replace('q=', '', $q));
            } else {
                $lang = $part;
                $q = 1.0;
            }
            $langCode = explode('-', $lang)[0];
            $languages[$langCode] = $q;
        }

        arsort($languages);

        foreach (array_keys($languages) as $langCode) {
            if (array_key_exists($langCode, config('locales.supported', []))) {
                return $langCode;
            }
        }

        return null;
    }
}
