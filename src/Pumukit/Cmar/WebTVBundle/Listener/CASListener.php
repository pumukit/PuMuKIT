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
    private $environment;

    public function __construct($casUrl, $casPort, $casUri, $casAllowedIpClients, $environment = 'prod')
    {
        $this->casUrl = $casUrl;
        $this->casPort = $casPort;
        $this->casUri = $casUri;
        $this->casAllowedIpClients = $casAllowedIpClients;
        $this->environment = $environment;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST) && ('test' !== $this->environment)){
            ob_start();
            try {
                \CAS_GracefullTerminationException::throwInsteadOfExiting();
                \phpCAS::client(CAS_VERSION_2_0, $this->casUrl, $this->casPort, $this->casUri, false);
                //\phpCAS::setDebug('/tmp/cas.log');
                \phpCAS::setNoCasServerValidation();
                //\phpCAS::setSingleSignoutCallback(array($this, 'casSingleSignOut'));
                //\phpCAS::setPostAuthenticateCallback(array($this, 'casPostAuth'));
                \phpCAS::handleLogoutRequests(true, $this->casAllowedIpClients);
            } catch (\Exception $e) {
            }
            ob_end_clean();
        }
    }
}