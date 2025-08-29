<?php

namespace GustavoCaiano\Lakeclient\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use GustavoCaiano\Lakeclient\Filament\Pages\LicensePage;
use GustavoCaiano\Lakeclient\Lakeclient;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLicensed
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Lakeclient $client */
        $client = app(Lakeclient::class);
        // Attempt a lazy heartbeat if the lease is close to expiring (server-driven via lease_expires_at)
        try {
            // Renew shortly before expiry (server-driven, based on lease_expires_at)
            if ($client->shouldRenewLease()) {
                $client->heartbeat();
            }
        } catch (\Throwable) {
            // Ignore lazy heartbeat errors to not block requests
        }

        if (! $client->isLicensed()) {
            $targetUrl = null;

            if (class_exists(Filament::class) && app()->bound('filament')) {
                $panelId = null;
                try {
                    $currentPanel = Filament::getCurrentPanel();
                    if (is_string($currentPanel)) {
                        $panelId = $currentPanel;
                    } elseif ($currentPanel && method_exists($currentPanel, 'getId')) {
                        /** @var string $panelId */
                        $panelId = $currentPanel->getId();
                    } else {
                        $panels = Filament::getPanels();
                        if (! empty($panels)) {
                            $first = reset($panels);
                            if (is_string($first)) {
                                $panelId = $first;
                            } elseif ($first && method_exists($first, 'getId')) {
                                /** @var string $panelId */
                                $panelId = $first->getId();
                            }
                        }
                    }

                    /** @var string|null $panelId */
                    $targetUrl = LicensePage::getUrl(panel: $panelId);
                } catch (\Throwable $e) {
                    $targetUrl = null;
                }
            }

            // If we cannot determine a target URL, do not redirect to avoid loops
            if (! $targetUrl) {
                return $next($request);
            }

            // Prevent redirect loop if we're already on the license page
            if (rtrim($request->fullUrl(), '/') === rtrim(url($targetUrl), '/')) {
                return $next($request);
            }

            return redirect($targetUrl);
        }

        return $next($request);
    }
}
