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

    public function __construct(
        UrlGeneratorInterface $router,
        SecureTokenService $secureTokenService
    ) {
        $this->router = $router;
        $this->secureTokenService = $secureTokenService;
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
        $tokenData = $this->secureTokenService->generateToken($track->id());

        $separator = str_contains($track->storage()->url()->url(), '?') ? '&' : '?';

        return sprintf(
            '%s%stoken=%s&expires=%d&resource=%s',
            $track->storage()->url()->url(),
            $separator,
            $tokenData['token'],
            $tokenData['expires'],
            urlencode($track->id())
        );
    }
}
