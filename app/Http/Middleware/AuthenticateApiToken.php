<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $plain = $this->extractToken($request);
        if ($plain === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing API token. Send Authorization: Bearer <token>.',
            ], 401);
        }

        $token = ApiToken::findActiveByPlainText($plain);
        if (! $token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or revoked API token.',
            ], 401);
        }

        foreach ($abilities as $ability) {
            if ($ability !== '' && ! $token->can($ability)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'API token is missing required ability: '.$ability,
                ], 403);
            }
        }

        $token->touchLastUsed();
        $request->attributes->set('api_token', $token);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = trim((string) $request->header('Authorization', ''));
        if (preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
            return $m[1];
        }

        $query = trim((string) $request->query('api_token', ''));

        return $query !== '' ? $query : null;
    }
}
