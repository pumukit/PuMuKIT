<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Services;

use Symfony\Component\HttpFoundation\Request;

/**
 * Service to generate and validate secure tokens for media file access.
 * Implements security measures against WSTG-ATHN-04 vulnerability.
 */
class SecureTokenService
{
    private string $secret;
    private int $tokenDuration;

    public function __construct(string $secret, int $tokenDuration = 3600)
    {
        if (empty($secret)) {
            throw new \InvalidArgumentException('Secret cannot be empty for secure token generation');
        }

        $this->secret = $secret;
        $this->tokenDuration = $tokenDuration;
    }

    /**
     * Generate a secure token for a resource.
     *
     * @param string $resourceId The resource identifier (track ID, path, etc.)
     * @param string|null $clientIp Client IP address for additional security
     * @param int|null $customExpiration Custom expiration timestamp
     *
     * @return array{token: string, expires: int}
     */
    public function generateToken(string $resourceId, ?string $clientIp = null, ?int $customExpiration = null): array
    {
        $expires = $customExpiration ?? (time() + $this->tokenDuration);
        $clientIp = $clientIp ?? '';

        // Generate a random salt for additional security
        $salt = bin2hex(random_bytes(16));

        // Create token data
        $tokenData = sprintf(
            '%s|%d|%s|%s',
            $resourceId,
            $expires,
            $clientIp,
            $salt
        );

        // Generate HMAC using SHA-256
        $hash = hash_hmac('sha256', $tokenData, $this->secret);

        // Combine salt and hash for the token
        $token = base64_encode($salt . '|' . $hash);

        // URL-safe token
        $token = strtr($token, '+/', '-_');
        $token = rtrim($token, '=');

        return [
            'token' => $token,
            'expires' => $expires,
            'salt' => $salt,
        ];
    }

    /**
     * Validate a secure token.
     *
     * @param string $token The token to validate
     * @param string $resourceId The resource identifier
     * @param int $expires The expiration timestamp
     * @param string|null $clientIp Client IP address
     *
     * @return bool True if token is valid, false otherwise
     */
    public function validateToken(string $token, string $resourceId, int $expires, ?string $clientIp = null): bool
    {
        // Check if token has expired
        if (time() > $expires) {
            return false;
        }

        try {
            // URL-safe decoding
            $token = strtr($token, '-_', '+/');
            $decodedToken = base64_decode($token, true);

            if ($decodedToken === false) {
                return false;
            }

            // Extract salt and hash
            $parts = explode('|', $decodedToken);
            if (count($parts) !== 2) {
                return false;
            }

            [$salt, $providedHash] = $parts;

            $clientIp = $clientIp ?? '';

            // Recreate token data
            $tokenData = sprintf(
                '%s|%d|%s|%s',
                $resourceId,
                $expires,
                $clientIp,
                $salt
            );

            // Generate expected hash
            $expectedHash = hash_hmac('sha256', $tokenData, $this->secret);

            // Constant-time comparison to prevent timing attacks
            return hash_equals($expectedHash, $providedHash);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate a secure URL for a resource.
     *
     * @param string $baseUrl The base URL
     * @param string $resourceId The resource identifier
     * @param string|null $clientIp Client IP address
     *
     * @return string The secure URL with token parameters
     */
    public function generateSecureUrl(string $baseUrl, string $resourceId, ?string $clientIp = null): string
    {
        $tokenData = $this->generateToken($resourceId, $clientIp);

        $separator = strpos($baseUrl, '?') !== false ? '&' : '?';

        return sprintf(
            '%s%stoken=%s&expires=%d&resource=%s',
            $baseUrl,
            $separator,
            $tokenData['token'],
            $tokenData['expires'],
            urlencode($resourceId)
        );
    }

    /**
     * Validate token from request.
     *
     * @param Request $request The HTTP request
     * @param string $resourceId The resource identifier
     *
     * @return bool True if token is valid, false otherwise
     */
    public function validateTokenFromRequest(Request $request, string $resourceId): bool
    {
        $token = $request->query->get('token');
        $expires = $request->query->getInt('expires', 0);
        $requestedResource = $request->query->get('resource');

        if (!$token || !$expires) {
            return false;
        }

        // Verify that the requested resource matches
        if ($requestedResource !== null && $requestedResource !== $resourceId) {
            return false;
        }

        $clientIp = $request->getClientIp();

        return $this->validateToken($token, $resourceId, $expires, $clientIp);
    }

    /**
     * Get token duration in seconds.
     */
    public function getTokenDuration(): int
    {
        return $this->tokenDuration;
    }
}

