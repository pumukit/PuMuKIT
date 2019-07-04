<?php

namespace Pumukit\SchemaBundle\Controller;

use Pumukit\SchemaBundle\Document\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/api/user")
 */
class APIUserController extends Controller
{
    /**
     * @Route("/users.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function allAction(Request $request)
    {
        $repo = $this
            ->get('doctrine_mongodb.odm.document_manager')
            ->getRepository(User::class)
        ;
        $serializer = $this->get('jms_serializer');

        $users = $repo->findAll();
        $data = $serializer->serialize($users, $request->getRequestFormat());

        return new Response($data);
    }
}
