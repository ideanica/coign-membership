<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetVisitorLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $country = config('app.use_vercel_geolocation') ? strtoupper((string) $request->header('x-vercel-ip-country', '')) : '';
        $locale = preg_match('/^[A-Z]{2}$/', $country) && ! in_array($country, ['XX', 'ZZ'], true) ? ($country === 'ID' ? 'id' : 'en') : $request->getPreferredLanguage(['id', 'en']);
        if ($request->is('admin', 'admin/*')) {
            $locale = 'id';
        }
        $previousLocale = app()->getLocale();
        app()->setLocale($locale ?? 'id');
        try {
            $response = $next($request);
            $response->headers->set('Content-Language', app()->getLocale());
            $response->setVary(['X-Vercel-IP-Country', 'Accept-Language'], false);

            return $response;
        } finally {
            app()->setLocale($previousLocale);
        }
    }
}
