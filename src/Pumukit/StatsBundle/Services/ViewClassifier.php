<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\Services;

use Detection\MobileDetect;

final class ViewClassifier
{
    public const SOURCES = ['portal', 'embed', 'lms', 'backoffice', 'external', 'direct'];
    public const DEVICES = ['desktop', 'mobile', 'tablet', 'unknown'];

    private const EMBED_PATH = '#^/([a-z]{2}/)?(iframe|player_iframe|videoplayer|livevideoplayer|player|video/iframe|mmobj/iframe|paella)(/|$)#i';
    private const LMS_PATH = '#^/(openedx|lms|lti)(/|$)#i';
    private const BACKOFFICE_PATH = '#^/admin(/|$)#i';

    private readonly MobileDetect $mobileDetect;
    private array $devices = [];

    public function __construct(private readonly string $frontendHost)
    {
        $this->mobileDetect = new MobileDetect();
    }

    public function source(?string $referer, ?string $requestHost = null): string
    {
        if (null === $referer || '' === $referer) {
            return 'direct';
        }

        $host = strtolower((string) parse_url($referer, PHP_URL_HOST));
        $ownHosts = array_map('strtolower', array_filter([$this->frontendHost, $requestHost]));
        if ('' === $host || !in_array($host, $ownHosts, true)) {
            return 'external';
        }

        $path = (string) parse_url($referer, PHP_URL_PATH);

        return match (true) {
            (bool) preg_match(self::LMS_PATH, $path) => 'lms',
            (bool) preg_match(self::BACKOFFICE_PATH, $path) => 'backoffice',
            (bool) preg_match(self::EMBED_PATH, $path) => 'embed',
            default => 'portal',
        };
    }

    public function device(?string $userAgent): string
    {
        if (null === $userAgent || '' === $userAgent) {
            return 'unknown';
        }

        if (!isset($this->devices[$userAgent])) {
            if (count($this->devices) >= 10000) {
                $this->devices = [];
            }
            $this->mobileDetect->setUserAgent($userAgent);
            $this->devices[$userAgent] = $this->mobileDetect->isTablet() ? 'tablet' : ($this->mobileDetect->isMobile() ? 'mobile' : 'desktop');
        }

        return $this->devices[$userAgent];
    }
}
