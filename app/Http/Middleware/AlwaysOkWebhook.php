<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AlwaysOkWebhook
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            report($e);
            return response('ok', 200);
        }

        if ($response->getStatusCode() >= 400) {
            report(new \RuntimeException('Webhook non-200: '.$response->getStatusCode()));
            return response('ok', 200);
        }

        return $response;
    }
}
