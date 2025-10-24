<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateHorizonAccess
{
    private const MAX_ATTEMPTS = 10;

    public function handle(Request $request, Closure $next): mixed
    {
        [$username, $password] = $this->credentials();

        if (
            !$this->isSecureConnection($request) ||
            !$this->hasBasicHeaders($request) ||
            !$this->matchesCredentials($request, $username, $password)
        ) {
            $this->hitRateLimiter($request);

            return response('Unauthorized.', Response::HTTP_UNAUTHORIZED, [
                'WWW-Authenticate' => 'Basic',
            ]);
        }

        return $next($request);
    }

    protected function credentials(): array
    {
        return [
            (string) config('horizon.username'),
            (string) config('horizon.password'),
        ];
    }

    protected function isSecureConnection(Request $request): bool
    {
        return app()->environment('production') ? $request->secure() : true;
    }

    protected function hasBasicHeaders(Request $request): bool
    {
        return $request->hasHeader('PHP_AUTH_USER') && $request->hasHeader('PHP_AUTH_PW');
    }

    protected function matchesCredentials(Request $request, string $username, string $password): bool
    {
        return hash_equals($username, (string) $request->getUser())
            && hash_equals($password, (string) $request->getPassword());
    }

    protected function hitRateLimiter(Request $request): void
    {
        $key = 'horizon-auth:'.$request->ip();
        RateLimiter::hit($key);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            abort(Response::HTTP_TOO_MANY_REQUESTS);
        }
    }
}
