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
    protected $documentManager;
    protected $serializer;

    public function __construct(DocumentManager $documentManager, SerializerService $serializer)
    {
        $this->documentManager = $documentManager;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/users.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     */
    public function allAction(Request $request): Response
    {
        $users = $this->documentManager->getRepository(User::class)->findAll();

        $userData = [
            'total' => is_countable($users) ? count($users) : 0,
            'users' => $users,
        ];

        $data = $this->serializer->dataSerialize($userData, $request->getRequestFormat());

        return new Response($data);
    }
}
