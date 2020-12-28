<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\SerializerService;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/user")
 */
class APIUserController extends AbstractController
{
    /**
     * @Route("/users.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function allAction(Request $request, DocumentManager $documentManager, SerializerService $serializer)
    {
        $repo = $documentManager->getRepository(User::class);

        $users = $repo->findAll();
        $data = $serializer->dataSerialize($users, $request->getRequestFormat());

        return new Response($data);
    }
}
