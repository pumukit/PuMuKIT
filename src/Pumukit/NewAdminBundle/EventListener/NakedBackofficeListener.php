<?php

namespace Pumukit\NewAdminBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class NakedBackofficeListener
{
    private $domain;
    private $background;
    private $color;
    private $customCssURL;

    public function __construct($domain, $background, $color = '#ED6D00', $customCssURL = null)
    {
        $this->domain = $domain;
        $this->background = $background;
        $this->color = $color;
        $this->customCssURL = $customCssURL;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $req = $event->getRequest();

        if ($req->getHttpHost() == $this->domain) {
            $req->attributes->set('nakedbackoffice', true);
            $req->attributes->set('nakedbackoffice_color', $this->background);
            $req->attributes->set('nakedbackoffice_main_color', $this->color);
            $req->attributes->set('nakedbackoffice_custom_css_url', $this->customCssURL);
        }
    }
}
