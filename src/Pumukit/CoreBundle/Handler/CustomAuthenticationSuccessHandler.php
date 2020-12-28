<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;

class CustomAuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler
{
    private const EXCEPTION_MESSAGE = 'Invalid login';
    private $documentManager;

    public function __construct(HttpUtils $httpUtils, DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;

        parent::__construct($httpUtils);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        if ($user instanceof User) {
            $this->updateUser($user);
        }

        return parent::onAuthenticationSuccess($request, $token);
    }

    private function updateUser(User $user): void
    {
        $user->resetLoginAttempts();
        $this->documentManager->flush();
    }
}
