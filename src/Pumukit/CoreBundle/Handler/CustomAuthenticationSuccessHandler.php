<?php

namespace Pumukit\CoreBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class CustomAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private const RETURNED_ROUTE = 'pumukit_newadmin_index';
    private const EXCEPTION_MESSAGE = 'Invalid login';
    private $router;
    private $documentManager;

    public function __construct(Router $router, DocumentManager $documentManager)
    {
        $this->router = $router;
        $this->documentManager = $documentManager;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        if ($user instanceof User) {
            $this->updateUser($user);

            $route = $this->router->generate(self::RETURNED_ROUTE);
            if ($route) {
                return new RedirectResponse($route, Response::HTTP_FOUND);
            }
        }

        throw new UsernameNotFoundException(self::EXCEPTION_MESSAGE);
    }

    private function updateUser(User $user): void
    {
        $user->resetLoginChecks();
        $this->documentManager->flush();
    }
}
