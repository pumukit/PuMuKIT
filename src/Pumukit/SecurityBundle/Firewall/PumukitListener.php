<?php

namespace Pumukit\SecurityBundle\Firewall;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;


class PumukitListener extends AbstractAuthenticationListener
{
  protected $tokenStorage;
  protected $authenticationManager;


  /**
   * {@inheritdoc}
   */

  public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, SessionAuthenticationStrategyInterface $sessionStrategy, HttpUtils $httpUtils, $providerKey, AuthenticationSuccessHandlerInterface $successHandler, AuthenticationFailureHandlerInterface $failureHandler, array $options = array(), LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null)
  {
    
    parent::__construct($securityContext, $authenticationManager, $sessionStrategy, $httpUtils, $providerKey, $successHandler, $failureHandler, $options, $logger, $dispatcher);
  }


  /**
   * {@inheritdoc}
   */
  protected function attemptAuthentication(Request $request)
  {

    \phpCAS::client(CAS_VERSION_2_0, "login.campusdomar.es", 443, "cas", false);
    //\phpCAS::setDebug('/tmp/cas.log');
    \phpCAS::setNoCasServerValidation();
    //\phpCAS::setSingleSignoutCallback(array($this, 'casSingleSignOut'));
    //\phpCAS::setPostAuthenticateCallback(array($this, 'casPostAuth'));
    \phpCAS::handleLogoutRequests(true, array());
    \phpCAS::forceAuthentication();

    $username = \phpCAS::getUser();


    $token = new PreAuthenticatedToken($username, array('ROLE_USER'), $this->providerKey);
    return $this->authenticationManager->authenticate($token);
  }
}