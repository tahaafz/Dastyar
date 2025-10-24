<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateTelescopeAccess
{
    const MAX_ATTEMPTS = 5;

    public function handle(Request $request, Closure $next): mixed
    {
        $config = config('custom.admin.telescope');

        $username = @$config['username'];
        $password = @$config['password'];

        if (
            !$this->isSecureConnection($request) ||
            !$this->isValidAuthorizationHeader($request) ||
            !$this->isValidCredentials($request, $username, $password)
        ) {
            $this->hitRateLimiter($request);
            return response('Unauthorized.', Response::HTTP_UNAUTHORIZED, ['WWW-Authenticate' => 'Basic']);
        }

        return $next($request);
    }

    private function isSecureConnection($request): bool
    {
        return app()->environment('production') ? $request->secure() : true;
    }

    private function isValidAuthorizationHeader($request): bool
    {
        return $request->hasHeader('PHP_AUTH_USER') && $request->hasHeader('PHP_AUTH_PW');
    }

    private function isValidCredentials($request, $username, $password): bool
    {
        return $request->getUser() === $username && $request->getPassword() === $password;
    }

    private function hitRateLimiter($request): void
    {
        $key = 'telescope-auth:' . $request->ip();
        RateLimiter::hit($key);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            abort(Response::HTTP_TOO_MANY_REQUESTS);
        }
    }
}
