<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;

class TrustConfiguredProxies extends TrustProxies
{
    protected function proxies()
    {
        // Resolve after environment/configuration bootstrap, when handling the request.
        return array_values(array_filter(array_map('trim', config('security.trusted_proxies', [])),
            fn (string $proxy) => $proxy !== '' && $proxy !== '*'));
    }
}
