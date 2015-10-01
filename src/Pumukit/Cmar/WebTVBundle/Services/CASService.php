<?php

namespace Pumukit\Cmar\WebTVBundle\Services;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CASService
{
    private $casUrl;
    private $casPort;
    private $casUri;
    private $casAllowedIpClients;
    private $environment;

    public function __construct($casUrl, $casPort, $casUri, $casAllowedIpClients)
    {
        $this->casUrl = $casUrl;
        $this->casPort = $casPort;
        $this->casUri = $casUri;
        $this->casAllowedIpClients = $casAllowedIpClients;

        $this->prepare();
    }

    private function prepare()
    {
        \phpCAS::client(CAS_VERSION_2_0, $this->casUrl, $this->casPort, $this->casUri, false);
        //\phpCAS::setDebug('/tmp/cas.log');
        \phpCAS::setNoCasServerValidation();
        //\phpCAS::setSingleSignoutCallback(array($this, 'casSingleSignOut'));
        //\phpCAS::setPostAuthenticateCallback(array($this, 'casPostAuth'));
        \phpCAS::handleLogoutRequests(true, $this->casAllowedIpClients);
    }

    public function isAuthenticated()
    {
        return \phpCAS::isAuthenticated();
    }

    public function getUser()
    {
        return \phpCAS::getUser();
    }

    public function setFixedServiceURL($url)
    {
        \phpCAS::setFixedServiceURL($url);
    }

    public function forceAuthentication()
    {
        \phpCAS::forceAuthentication();
    }

    public function logoutWithRedirectService($url)
    {
        \phpCAS::logoutWithRedirectService($url);
    }
}