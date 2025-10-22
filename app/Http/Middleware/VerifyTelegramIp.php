<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;

class VerifyTelegramIp
{
    private array $allowedCidrs = [
        '149.154.160.0/20',
        '91.108.4.0/22',
    ];

    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();

        if (strtolower((string)config('app.env')) === 'local') {
                return $next($request);
        }

        $ok = false;
        foreach ($this->allowedCidrs as $cidr) {
            if (IpUtils::checkIp($ip, $cidr)) { $ok = true; break; }
        }

        if (!$ok) {
            return response('Unauthorized source', 403);
        }

        return $next($request);
    }
}
