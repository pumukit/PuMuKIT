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
    private ?string $secret;
    private int $secureDuration;

    public function __construct(UrlGeneratorInterface $router, ?string $secret, int $secureDuration)
    {
        $this->router = $router;
        $this->secret = $secret;
        $this->secureDuration = $secureDuration;
    }

    public function generateTrackFileUrl(MediaInterface $track, int $reference_type = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        $ext = pathinfo(parse_url($track->storage()->url()->url(), PHP_URL_PATH), PATHINFO_EXTENSION);
        if (!$ext) {
            $ext = pathinfo($track->storage()->path()->path(), PATHINFO_EXTENSION);
        }

        $params = [
            'id' => $track->id(),
            'ext' => $ext,
        ];

        return $this->router->generate('pumukit_trackfile_index', $params, $reference_type);
    }

    public function generateDirectTrackFileUrl(Track $track, Request $request): string
    {
        $timestamp = time() + $this->secureDuration;
        $hash = $this->getHash($track, $timestamp, $this->secret, $request->getClientIp());

        return $track->storage()->url()."?md5={$hash}&expires={$timestamp}&".http_build_query($request->query->all(), '', '&');
    }

    protected function getHash(Track $track, int $timestamp, ?string $secret, string $ip): string
    {
        $url = $track->storage()->url()->url();
        $path = parse_url($url, PHP_URL_PATH);

        return str_replace('=', '', strtr(base64_encode(md5("{$timestamp}{$path}{$ip} {$secret}", true)), '+/', '-_'));
    }
}
