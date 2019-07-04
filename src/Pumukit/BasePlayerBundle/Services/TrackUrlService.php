<?php

namespace Pumukit\BasePlayerBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TrackUrlService
{
    private $dm;
    private $router;
    private $secret;
    private $secureDuration;

    public function __construct(DocumentManager $dm, UrlGeneratorInterface $router, $secret, $secureDuration)
    {
        $this->dm = $dm;
        $this->router = $router;
        $this->secret = $secret;
        $this->secureDuration = $secureDuration;
    }

    /**
     * @param Track $track
     * @param int   $reference_type
     *
     * @return string
     */
    public function generateTrackFileUrl(Track $track, $reference_type = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        $ext = pathinfo(parse_url($track->getUrl(), PHP_URL_PATH), PATHINFO_EXTENSION);
        if (!$ext) {
            $ext = pathinfo($track->getPath(), PATHINFO_EXTENSION);
        }

        $params = [
            'id' => $track->getId(),
            'ext' => $ext,
        ];

        return $this->router->generate('pumukit_trackfile_index', $params, $reference_type);
    }

    /**
     * @param Track $track
     * @param $request
     *
     * @throws \Exception
     *
     * @return string
     */
    public function generateDirectTrackFileUrl(Track $track, $request)
    {
        $timestamp = time() + $this->secureDuration;
        $hash = $this->getHash($track, $timestamp, $this->secret, $request->getClientIp());

        return $track->getUrl()."?md5={$hash}&expires={$timestamp}&".http_build_query($request->query->all(), null, '&');
    }

    /**
     * @param Track $track
     * @param $timestamp
     * @param $secret
     * @param $ip
     *
     * @return mixed
     */
    protected function getHash(Track $track, $timestamp, $secret, $ip)
    {
        $url = $track->getUrl();
        $path = parse_url($url, PHP_URL_PATH);

        return str_replace('=', '', strtr(base64_encode(md5("{$timestamp}{$path}{$ip} {$secret}", true)), '+/', '-_'));
    }
}
