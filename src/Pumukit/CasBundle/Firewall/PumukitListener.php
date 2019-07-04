<?php

namespace Pumukit\CasBundle\Firewall;

use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Pumukit\CasBundle\Services\CASService;

/**
 * Class PumukitListener.
 */
class PumukitListener extends AbstractAuthenticationListener
{
    protected $casService;

    /**
     * {@inheritdoc}
     */
    public function __construct(TokenStorageInterface $tokenStorage, AuthenticationManagerInterface $authenticationManager, SessionAuthenticationStrategyInterface $sessionStrategy, HttpUtils $httpUtils, $providerKey, AuthenticationSuccessHandlerInterface $successHandler, AuthenticationFailureHandlerInterface $failureHandler, array $options = [], LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null, CASService $casService = null)
    {
        parent::__construct($tokenStorage, $authenticationManager, $sessionStrategy, $httpUtils, $providerKey, $successHandler, $failureHandler, $options, $logger, $dispatcher);

        $this->casService = $casService;
        $this->options['require_previous_session'] = false;
    }

    /**
     * {@inheritdoc}
     */
    protected function attemptAuthentication(Request $request)
    {
        $this->casService->forceAuthentication();
        $username = $this->casService->getUser();

        $token = new PreAuthenticatedToken($username, ['ROLE_USER'], $this->providerKey);

        return $this->authenticationManager->authenticate($token);
    }
}
