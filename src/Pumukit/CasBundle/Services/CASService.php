<?php

namespace Pumukit\CasBundle\Services;

/**
 * Class CASService.
 */
class CASService
{
    private $casUrl;
    private $casPort;
    private $casUri;
    private $casAllowedIpClients;
    private $initialize = false;
    private $env;
    private $cacheDir;

    /**
     * CASService constructor.
     *
     * @param        $casUrl
     * @param        $casPort
     * @param        $casUri
     * @param        $casAllowedIpClients
     * @param string $env
     * @param null   $cacheDir
     */
    public function __construct($casUrl, $casPort, $casUri, $casAllowedIpClients, $env = 'prod', $cacheDir = null)
    {
        $this->casUrl = $casUrl;
        $this->casPort = $casPort;
        $this->casUri = $casUri;
        $this->casAllowedIpClients = $casAllowedIpClients;
        $this->env = $env;
        $this->cacheDir = $cacheDir;
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        if (!$this->initialize) {
            $this->prepare();
        }

        return \phpCAS::isAuthenticated();
    }

    /**
     * @return string
     */
    public function getUser()
    {
        if (!$this->initialize) {
            $this->prepare();
        }

        return \phpCAS::getUser();
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        if (!$this->initialize) {
            $this->prepare();
        }

        return \phpCAS::getAttributes();
    }

    /**
     * @param $url
     */
    public function setFixedServiceURL($url)
    {
        if (!$this->initialize) {
            $this->prepare();
        }
        \phpCAS::setFixedServiceURL($url);
    }

    public function forceAuthentication()
    {
        if (!$this->initialize) {
            $this->prepare();
        }
        \phpCAS::forceAuthentication();
    }

    public function logoutWithRedirectService($url)
    {
        if (!$this->initialize) {
            $this->prepare();
        }
        \phpCAS::logoutWithRedirectService($url);
    }

    public function logout()
    {
        if (!$this->initialize) {
            $this->prepare();
        }
        \phpCAS::logout();
    }

    private function prepare()
    {
        $this->initialize = true;
        \phpCAS::client(CAS_VERSION_2_0, $this->casUrl, $this->casPort, $this->casUri, true);
        \phpCAS::setNoCasServerValidation();
        if ('dev' == $this->env) {
            \phpCAS::setDebug($this->cacheDir ? ($this->cacheDir.'/cas.log') : '/tmp/cas.log');
        }
        if ($this->casAllowedIpClients) {
            \phpCAS::handleLogoutRequests(true, $this->casAllowedIpClients);
        } else {
            \phpCAS::handleLogoutRequests(false);
        }
    }
}
