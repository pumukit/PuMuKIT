<?php

namespace Pumukit\LDAPBundle\EventListener;

use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Pumukit\LDAPBundle\Services\LDAPService;
use Pumukit\LDAPBundle\Services\LDAPUserService;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AuthenticationHandler implements AuthenticationSuccessHandlerInterface
{
    const LDAP_ID_KEY = 'uid';

    protected $LDAPService;
    protected $LDAPUserService;
    protected $HttpUtils;
    protected $session;

    public function __construct(ContainerInterface $container, LDAPService $LDAPService, LDAPUserService $LDAPUserService, HttpUtils $HttpUtils, Session $session)
    {
        $this->container = $container;
        $this->ldapService = $LDAPService;
        $this->ldapUserService = $LDAPUserService;
        $this->httpUtils = $HttpUtils;
        $this->session = $session;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $info = $this->ldapService->getInfoFrom(self::LDAP_ID_KEY, $token->getUser()->getUsername());
        if (!isset($info) || !$info) {
            throw new \RuntimeException('User not found.');
        }

        $user = $this->ldapUserService->createUser($info);

        $token = new UsernamePasswordToken($user, null, 'user', $user->getRoles());
        $this->container->get('security.context')->setToken($token);

        return $this->httpUtils->createRedirectResponse($request, $this->determineTargetUrl());
    }

    private function determineTargetUrl()
    {
        if (null !== $this->session->get('_security.main.target_path')) {
            return $this->session->get('_security.main.target_path');
        } else {
            return $this->session->get('target_path');
        }
    }
}
