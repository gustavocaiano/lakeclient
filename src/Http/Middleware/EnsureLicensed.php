<?php

namespace GustavoCaiano\Windclient\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use GustavoCaiano\Windclient\Filament\Pages\LicensePage;
use GustavoCaiano\Windclient\Windclient;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLicensed
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Windclient $client */
        $client = app(Windclient::class);
        if (! $client->isLicensed()) {
            $targetUrl = null;

            if (class_exists(Filament::class) && app()->bound('filament')) {
                $panel = Filament::getCurrentPanel();
                try {
                    $targetUrl = LicensePage::getUrl(panel: $panel);
                } catch (\Throwable $e) {
                    $targetUrl = null;
                }
            }

            // Prevent redirect loop if we're already on the license page
            if ($targetUrl && rtrim($request->fullUrl(), '/') === rtrim(url($targetUrl), '/')) {
                return $next($request);
            }

            return redirect($targetUrl ?: '/');
        }

        return $next($request);
    }
}
