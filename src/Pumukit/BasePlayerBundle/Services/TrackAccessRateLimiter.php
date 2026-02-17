<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Rate limiter service to prevent enumeration attacks on track IDs.
 * Implements protection against WSTG-ATHN-04 enumeration attacks.
 */
class TrackAccessRateLimiter
{
    private const MAX_ATTEMPTS_PER_IP = 20;
    private const TIME_WINDOW_SECONDS = 60;
    private const BAN_DURATION_SECONDS = 300; // 5 minutes

    private array $attempts = [];
    private array $bannedIps = [];
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Check if the IP is allowed to make a request.
     * Returns false if the IP is rate-limited or banned.
     */
    public function isAllowed(string $ip): bool
    {
        $this->cleanupOldData();

        // Check if IP is banned
        if (isset($this->bannedIps[$ip])) {
            $banExpiry = $this->bannedIps[$ip];
            if (time() < $banExpiry) {
                $this->logger->warning(sprintf(
                    'Banned IP %s attempted to access tracks (ban expires in %d seconds)',
                    $ip,
                    $banExpiry - time()
                ));
                return false;
            }
            unset($this->bannedIps[$ip]);
        }

        // Check rate limit
        if (!isset($this->attempts[$ip])) {
            $this->attempts[$ip] = [];
        }

        // Count recent attempts in the time window
        $recentAttempts = array_filter(
            $this->attempts[$ip],
            fn($timestamp) => (time() - $timestamp) < self::TIME_WINDOW_SECONDS
        );

        if (count($recentAttempts) >= self::MAX_ATTEMPTS_PER_IP) {
            // Ban the IP
            $this->bannedIps[$ip] = time() + self::BAN_DURATION_SECONDS;
            $this->logger->error(sprintf(
                'IP %s exceeded rate limit (%d attempts in %d seconds) - BANNED for %d seconds',
                $ip,
                count($recentAttempts),
                self::TIME_WINDOW_SECONDS,
                self::BAN_DURATION_SECONDS
            ));
            return false;
        }

        return true;
    }

    /**
     * Register an attempt from an IP.
     */
    public function registerAttempt(string $ip): void
    {
        if (!isset($this->attempts[$ip])) {
            $this->attempts[$ip] = [];
        }
        $this->attempts[$ip][] = time();
    }

    /**
     * Register a failed attempt (invalid token, not found, etc).
     * Failed attempts count more heavily.
     */
    public function registerFailedAttempt(string $ip): void
    {
        if (!isset($this->attempts[$ip])) {
            $this->attempts[$ip] = [];
        }

        // Failed attempts count as 3 regular attempts
        $now = time();
        $this->attempts[$ip][] = $now;
        $this->attempts[$ip][] = $now;
        $this->attempts[$ip][] = $now;

        $this->logger->notice(sprintf(
            'Failed track access attempt from IP %s (total: %d attempts)',
            $ip,
            count($this->attempts[$ip])
        ));
    }

    /**
     * Clean up old data to prevent memory leaks.
     */
    private function cleanupOldData(): void
    {
        $now = time();
        $cutoff = $now - self::TIME_WINDOW_SECONDS - self::BAN_DURATION_SECONDS;

        // Clean up old attempts
        foreach ($this->attempts as $ip => $timestamps) {
            $this->attempts[$ip] = array_filter(
                $timestamps,
                fn($timestamp) => $timestamp > $cutoff
            );
            if (empty($this->attempts[$ip])) {
                unset($this->attempts[$ip]);
            }
        }

        // Clean up expired bans
        foreach ($this->bannedIps as $ip => $expiry) {
            if ($now >= $expiry) {
                unset($this->bannedIps[$ip]);
                $this->logger->info(sprintf('Ban expired for IP %s', $ip));
            }
        }
    }

    /**
     * Get statistics for monitoring.
     */
    public function getStats(): array
    {
        $this->cleanupOldData();

        return [
            'active_ips' => count($this->attempts),
            'banned_ips' => count($this->bannedIps),
            'total_attempts' => array_sum(array_map('count', $this->attempts)),
        ];
    }

    /**
     * Check if an IP is currently banned.
     */
    public function isBanned(string $ip): bool
    {
        if (!isset($this->bannedIps[$ip])) {
            return false;
        }

        if (time() >= $this->bannedIps[$ip]) {
            unset($this->bannedIps[$ip]);
            return false;
        }

        return true;
    }

    /**
     * Manually ban an IP (for admin actions).
     */
    public function banIp(string $ip, int $durationSeconds = null): void
    {
        $duration = $durationSeconds ?? self::BAN_DURATION_SECONDS;
        $this->bannedIps[$ip] = time() + $duration;

        $this->logger->warning(sprintf(
            'IP %s manually banned for %d seconds',
            $ip,
            $duration
        ));
    }

    /**
     * Manually unban an IP (for admin actions).
     */
    public function unbanIp(string $ip): void
    {
        if (isset($this->bannedIps[$ip])) {
            unset($this->bannedIps[$ip]);
            $this->logger->info(sprintf('IP %s manually unbanned', $ip));
        }
    }
}

