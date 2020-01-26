<?php

namespace Pumukit\CoreBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\HttpUtils;

class CustomAuthenticationFailureHandler extends DefaultAuthenticationFailureHandler
{
    private const RETURNED_ROUTE = 'pumukit_auth';
    private const EXCEPTION_MESSAGE = 'Invalid login';
    private $documentManager;

    public function __construct(HttpKernelInterface $httpKernel, HttpUtils $httpUtils, DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
        parent::__construct($httpKernel, $httpUtils);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $username = $request->request->get('_username');
        if (!$username) {
            throw new UsernameNotFoundException(self::EXCEPTION_MESSAGE);
        }

        $user = $this->documentManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if (!$user) {
            throw new UsernameNotFoundException(self::EXCEPTION_MESSAGE);
        }

        $this->updateUser($user);

        return parent::onAuthenticationFailure($request, $exception);
    }

    private function updateUser(User $user): void
    {
        $user->addLoginAttempt();

        if ($user->isResetLoginAttemptsAllowed()) {
            $user->resetLoginAttempts();
        }

        $this->documentManager->flush();
    }

    private function setSessionException(?SessionInterface $session, AuthenticationException $exception)
    {
        if ($session) {
            $session->set(Security::AUTHENTICATION_ERROR, $exception);
        }
    }
}
