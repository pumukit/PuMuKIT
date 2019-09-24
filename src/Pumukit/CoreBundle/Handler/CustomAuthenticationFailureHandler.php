<?php

namespace Pumukit\CoreBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class CustomAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    private const RETURNED_ROUTE = 'pumukit_auth';
    private const EXCEPTION_MESSAGE = 'Invalid login';
    private $router;
    private $documentManager;

    public function __construct(Router $router, DocumentManager $documentManager)
    {
        $this->router = $router;
        $this->documentManager = $documentManager;
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

        $route = $this->router->generate(self::RETURNED_ROUTE);
        if (!$route) {
            throw new RouteNotFoundException(self::EXCEPTION_MESSAGE);
        }

        return new RedirectResponse($route, Response::HTTP_FOUND);
    }

    private function updateUser(User $user): void
    {
        $user->addLoginAttempt();
        $this->documentManager->flush();
    }
}
