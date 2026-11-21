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

    public function generateSecureUrl(string $baseUrl, string $resourceId, ?int $validFor = null): string
    {
        $tokenData = $this->generateToken($resourceId, time() + ($validFor ?? $this->tokenDuration));

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

    public function generateDownloadUrl(string $url, ?int $validFor = null): string
    {
        return $this->generateSecureUrl($url, rawurldecode((string) parse_url($url, PHP_URL_PATH)), $validFor);
    }

    public function generateHlsUrl(string $downloadServerUrl, string $directory, string $fileName, ?int $validFor = null): string
    {
        $tokenData = $this->generateToken($directory, time() + ($validFor ?? $this->tokenDuration));

        return sprintf(
            '%s/hls/%d/%s%s%s',
            rtrim($downloadServerUrl, '/'),
            $tokenData['expires'],
            $tokenData['token'],
            $directory,
            rawurlencode($fileName)
        );
    }

    public function validateHlsPath(string $path): bool
    {
        $segments = explode('/', $path);
        if ('' !== array_shift($segments)) {
            return false;
        }

        foreach ($segments as $segment) {
            if (1 === preg_match('/[\x00-\x1f\x7f]/', $segment)) {
                return false;
            }
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                return false;
            }
        }

        if (count($segments) < 8 || 'hls' !== $segments[0] || 'files' !== $segments[3] || 'downloads' !== $segments[4]) {
            return false;
        }

        if (!ctype_digit($segments[1])) {
            return false;
        }

        $last = $segments[count($segments) - 1];
        if (!str_ends_with($last, '.m3u8') && !str_ends_with($last, '.ts')) {
            return false;
        }

        $directory = sprintf('/files/downloads/%s/%s/', $segments[5], $segments[6]);

        return $this->validateToken($segments[2], $directory, (int) $segments[1]);
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
