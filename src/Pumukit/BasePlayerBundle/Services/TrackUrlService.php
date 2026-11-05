<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Services;

use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TrackUrlService
{
    private UrlGeneratorInterface $router;
    private SecureTokenService $secureTokenService;
    private ?string $downloadServerUrl;

    public function __construct(
        UrlGeneratorInterface $router,
        SecureTokenService $secureTokenService,
        ?string $downloadServerUrl = null
    ) {
        $this->router = $router;
        $this->secureTokenService = $secureTokenService;
        $this->downloadServerUrl = $downloadServerUrl;
    }

    public function generateTrackFileUrl(MediaInterface $track, int $reference_type = UrlGeneratorInterface::ABSOLUTE_PATH, bool $forceDownload = false): string
    {
        $ext = pathinfo(parse_url($track->storage()->url()->url(), PHP_URL_PATH), PATHINFO_EXTENSION);
        if (!$ext) {
            $ext = pathinfo($track->storage()->path()->path(), PATHINFO_EXTENSION);
        }

        $params = [
            'id' => $track->id(),
            'ext' => $ext,
        ];

        $baseUrl = $this->router->generate('pumukit_trackfile_index', $params, $reference_type);

        if ($forceDownload) {
            $baseUrl .= (str_contains($baseUrl, '?') ? '&' : '?').'forcedl=1';
        }

        $tokenData = $this->secureTokenService->generateToken($track->id());
        $separator = str_contains($baseUrl, '?') ? '&' : '?';
        $baseUrl .= sprintf(
            '%stoken=%s&expires=%d&resource=%s',
            $separator,
            $tokenData['token'],
            $tokenData['expires'],
            urlencode($track->id())
        );

        return $baseUrl;
    }

    public function generateDirectTrackFileUrl(Track $track, ?Request $request = null): string
    {
        return $this->secureTokenService->generateDownloadUrl($track->storage()->url()->url());
    }

    public function isOnDownloadServer(string $url): bool
    {
        return !empty($this->downloadServerUrl) && str_starts_with($url, rtrim($this->downloadServerUrl, '/').'/');
    }

    public function signForDownloadServer(string $url): string
    {
        return $this->isOnDownloadServer($url) ? $this->secureTokenService->generateDownloadUrl($url) : $url;
    }
}
