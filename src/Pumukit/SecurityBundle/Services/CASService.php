<?php

namespace Pumukit\SecurityBundle\Services;

class CASService
{
    private $casUrl;
    private $casPort;
    private $casUri;
    private $casAllowedIpClients;
    private $environment;
    private $initialize = false;

    public function __construct($casUrl, $casPort, $casUri, $casAllowedIpClients)
    {
        $this->casUrl = $casUrl;
        $this->casPort = $casPort;
        $this->casUri = $casUri;
        $this->casAllowedIpClients = $casAllowedIpClients;
//        $this->prepare();
    }

    private function prepare()
    {
        $initialize = true;
        \phpCAS::client(CAS_VERSION_2_0, $this->casUrl, $this->casPort, $this->casUri, false);
        //\phpCAS::setDebug('/tmp/cas.log');
        \phpCAS::setNoCasServerValidation();
        //\phpCAS::setSingleSignoutCallback(array($this, 'casSingleSignOut'));
        //\phpCAS::setPostAuthenticateCallback(array($this, 'casPostAuth'));
        \phpCAS::handleLogoutRequests(true, $this->casAllowedIpClients);
    }

    public function isAuthenticated()
    {
        if(!$initialize) $this->prepare();
        return \phpCAS::isAuthenticated();
    }

    public function getUser()
    {
        if(!$initialize) $this->prepare();
        return \phpCAS::getUser();
    }

    public function getAttributes()
    {
        return \phpCAS::getAttributes();
    }

    public function setFixedServiceURL($url)
    {
        if(!$initialize) $this->prepare();
        \phpCAS::setFixedServiceURL($url);
    }

    public function forceAuthentication()
    {
        if(!$initialize) $this->prepare();
        \phpCAS::forceAuthentication();
    }

    public function logoutWithRedirectService($url)
    {
        if(!$initialize) $this->prepare();
        \phpCAS::logoutWithRedirectService($url);
    }

    public function logout()
    {
        if(!$initialize) $this->prepare();
        \phpCAS::logout();
    }
}
