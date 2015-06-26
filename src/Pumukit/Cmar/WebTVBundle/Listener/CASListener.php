<?php

namespace Pumukit\Cmar\WebTVBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CASListener
{
    private $casUrl;
    private $casPort;
    private $casUri;
    private $casAllowedIpClients;

    public function __construct($casUrl, $casPort, $casUri, $casAllowedIpClients)
    {
        $this->casUrl = $casUrl;
        $this->casUrl = $casPort;
        $this->casUrl = $casUri;
        $this->casUrl = $casAllowedIpClients;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST) {
            \phpCAS::client(CAS_VERSION_2_0, $this->casUrl, $this->casPort, $this->casUri, false);
            //\phpCAS::setDebug('/tmp/cas.log');
            \phpCAS::setNoCasServerValidation();
            //\phpCAS::setSingleSignoutCallback(array($this, 'casSingleSignOut'));
            //\phpCAS::setPostAuthenticateCallback(array($this, 'casPostAuth'));
            \phpCAS::handleLogoutRequests(true, $this->casAllowedIpClients);
        }
    }
}