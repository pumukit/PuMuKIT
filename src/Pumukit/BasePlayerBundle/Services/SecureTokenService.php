<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Services;

use Symfony\Component\HttpFoundation\Request;

class SecureTokenService
{
    private string $secret;
    private int $tokenDuration;

    public function __construct(string $secret, int $tokenDuration = 3600)
    {
        $this->secret = $secret;
        $this->tokenDuration = $tokenDuration;
    }

    public function generateToken(string $resourceId, ?int $customExpiration = null): array
    {
        $this->ensureSecretConfigured();
        $expires = $customExpiration ?? (time() + $this->tokenDuration);
        $tokenData = sprintf('%s|%d', $resourceId, $expires);

        $hash = hash_hmac('sha256', $tokenData, $this->secret);

        $token = strtr(base64_encode($hash), '+/', '-_');
        $token = rtrim($token, '=');

        return [
            'token' => $token,
            'expires' => $expires,
        ];
    }

    public function validateToken(string $token, string $resourceId, int $expires): bool
    {
        $this->ensureSecretConfigured();

        if (time() > $expires) {
            return false;
        }

        try {
            $token = strtr($token, '-_', '+/');
            $providedHash = base64_decode($token, true);

            if (false === $providedHash) {
                return false;
            }

            $tokenData = sprintf('%s|%d', $resourceId, $expires);

            $expectedHash = hash_hmac('sha256', $tokenData, $this->secret);

            return hash_equals($expectedHash, $providedHash);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function generateSecureUrl(string $baseUrl, string $resourceId): string
    {
        $tokenData = $this->generateToken($resourceId);

        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return sprintf(
            '%s%stoken=%s&expires=%d&resource=%s',
            $baseUrl,
            $separator,
            $tokenData['token'],
            $tokenData['expires'],
            urlencode($resourceId)
        );
    }

    public function validateTokenFromRequest(Request $request, string $resourceId): bool
    {
        $token = $request->query->get('token');
        $expires = $request->query->getInt('expires', 0);
        $requestedResource = $request->query->get('resource');

        if (!$token || !$expires) {
            return false;
        }

        if (null !== $requestedResource && $requestedResource !== $resourceId) {
            return false;
        }

        return $this->validateToken($token, $resourceId, $expires);
    }

    public function getTokenDuration(): int
    {
        return $this->tokenDuration;
    }

    private function ensureSecretConfigured(): void
    {
        if (empty($this->secret)) {
            throw new \RuntimeException(
                'PUMUKITPLAYER_SECURE_SECRET is not configured. '
                .'Please set it in your .env file. '
                .'Generate one with: php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"'
            );
        }
    }
}
