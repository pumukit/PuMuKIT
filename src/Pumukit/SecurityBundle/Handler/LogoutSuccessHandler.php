<?php

namespace Pumukit\SecurityBundle\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
  private $options;
  private $router;

  public function __construct(array $options = array(), UrlGeneratorInterface $router)
  {
    $this->options = $options;
    $this->router = $router;
  }

  public function onLogoutSuccess(Request $request)
  {
    \phpCAS::client(CAS_VERSION_2_0, "login.campusdomar.es", 443, "cas", false);
    \phpCAS::logout();

    /* Call CAS API to do authentication */
    /*
    \phpCAS::client($this->options['cas_protocol'], $this->options['cas_server'], $this->options['cas_port'], $this->options['cas_path'], false);
    if (!isset($this->options['cas_logout']) || empty($this->options['cas_logout'])) {
      \phpCAS::logout();
    } else {
      // generate absolute URL
      $url = $this->router->generate($this->options['cas_logout'], array(), true);
      \phpCAS::logoutWithRedirectService($url);
    }
    return null;
    */
  }
}