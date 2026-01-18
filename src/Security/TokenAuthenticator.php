<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class TokenAuthenticator
{
    public function getPhoneFromRequest(Request $request): ?string
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authHeader, 7);
        $decoded = base64_decode($token);

        if (!$decoded || !str_contains($decoded, '|')) {
            return null;
        }

        [$phone] = explode('|', $decoded);

        return $phone ?: null;
    }

    public function unauthorized(): JsonResponse
    {
        return new JsonResponse(
            ['message' => 'Unauthorized'],
            401
        );
    }
}
