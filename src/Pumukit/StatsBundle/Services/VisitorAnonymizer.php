<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\Services;

final class VisitorAnonymizer
{
    public function __construct(private readonly string $secret) {}

    public function anonymizeIp(?string $ip): ?string
    {
        if (null === $ip || '' === $ip) {
            return null;
        }

        $packed = @inet_pton($ip);
        if (false === $packed) {
            return null;
        }

        $keep = 4 === strlen($packed) ? 3 : 6;
        $masked = substr($packed, 0, $keep).str_repeat("\0", strlen($packed) - $keep);

        return inet_ntop($masked) ?: null;
    }

    public function visitor(?string $ip, ?string $userAgent, \DateTimeInterface $date): string
    {
        return substr(hash_hmac('sha256', ($ip ?? '').'|'.($userAgent ?? '').'|'.$date->format('Y-m-d'), $this->secret), 0, 20);
    }
}
