<?php

namespace Vonso\FaspayTestLab\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTestLabEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $enabled = config('faspay-test-lab.enabled');

        if ($enabled === true || ($enabled === null && app()->environment('local', 'testing'))) {
            return $next($request);
        }

        abort(404, 'Faspay Test Lab is disabled in this environment.');
    }
}
