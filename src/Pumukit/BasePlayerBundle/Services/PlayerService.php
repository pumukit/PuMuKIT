<?php

namespace Pumukit\BasePlayerBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class PlayerService
{
    /**
     * @var Router
     */
    private $router;

    /**
     * PlayerService constructor.
     *
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param MultimediaObject $multimediaObject
     *
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
     * @param MultimediaObject $multimediaObject
     *
     * @return mixed
     */
    public function getMagicControllerPlayer(MultimediaObject $multimediaObject)
    {
        $url = $this->router->generate('pumukit_videoplayer_magicindex', ['id' => $multimediaObject->getId()]);
        $url = $this->cleanUrl($url);
        $endpoint = $this->router->match($url);

        return $endpoint['_controller'];
    }

    /**
     * @param $url
     *
     * @return mixed
     */
    private function cleanUrl($url)
    {
        return str_replace('app_dev.php/', '', $url);
    }
}
