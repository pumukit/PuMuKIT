<?php

namespace Pumukit\CoreBundle\Controller;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestBackofficeController extends Controller implements AdminControllerInterface
{
    /**
     * @Route("/test/backofficefilter.{_format}", name="pumukit_core_tests_backofficefilter", defaults={"_format":"json"})
     */
    public function testAction(Request $request)
    {
        $mmobjRepo = $this
            ->get('doctrine_mongodb.odm.document_manager')
            ->getRepository(MultimediaObject::class)
        ;
        $data = $mmobjRepo->createQueryBuilder()->distinct('_id')->getQuery()->execute();
        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($data, $request->getRequestFormat());

        return new Response($response);
    }
}
