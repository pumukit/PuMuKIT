<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Routing\RouterInterface;

class PlayerService
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * PlayerService constructor.
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @return mixed
     */
    public function getPublicControllerPlayer(MultimediaObject $multimediaObject)
    {
        $url = $this->router->generate('pumukit_videoplayer_index', ['id' => $multimediaObject->getId()]);
        $url = $this->cleanUrl($url);
        $endpoint = $this->router->match($url);

        return $endpoint['_controller'];
    }

    /**
     * @return mixed
     */
    public function getMagicControllerPlayer(MultimediaObject $multimediaObject)
    {
        $url = $this->router->generate('pumukit_videoplayer_magicindex', ['secret' => $multimediaObject->getSecret()]);
        $url = $this->cleanUrl($url);
        $endpoint = $this->router->match($url);

        return $endpoint['_controller'];
    }

    /**
     * @param string $url
     *
     * @return mixed
     */
    private function cleanUrl($url)
    {
        return str_replace('app_dev.php/', '', $url);
    }
}
