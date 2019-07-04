<?php

namespace Pumukit\LDAPBundle\EventListener;

use Pumukit\LDAPBundle\Services\LDAPService;
use Pumukit\LDAPBundle\Services\LDAPUserService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationHandler implements AuthenticationSuccessHandlerInterface
{
    const LDAP_ID_KEY = 'uid';

    protected $session;
    protected $container;
    protected $ldapService;
    protected $ldapUserService;
    protected $httpUtils;

    public function __construct(ContainerInterface $container, LDAPService $LDAPService, LDAPUserService $LDAPUserService, HttpUtils $HttpUtils, SessionInterface $session)
    {
        $this->container = $container;
        $this->ldapService = $LDAPService;
        $this->ldapUserService = $LDAPUserService;
        $this->httpUtils = $HttpUtils;
        $this->session = $session;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $username = strtolower($token->getUser()->getUsername());

        $info = $this->ldapService->getInfoFrom(self::LDAP_ID_KEY, $username);
        if (!isset($info) || !$info) {
            throw new \RuntimeException('User "'.$username.'" not found in LDAP (using LDAP '.self::LDAP_ID_KEY.' attribute).');
        }

        $user = $this->ldapUserService->createUser($info, $username);

        $token = new UsernamePasswordToken($user, null, 'user', $user->getRoles());
        $this->container->get('security.token_storage')->setToken($token);

        return $this->httpUtils->createRedirectResponse($request, $this->determineTargetUrl());
    }

    private function determineTargetUrl()
    {
        if (null !== $this->session->get('_security.main.target_path')) {
            return $this->session->get('_security.main.target_path');
        }
        if (null !== $this->session->get('target_path')) {
            return $this->session->get('target_path');
        }

        return 'homepage';
    }
}
