<?php

namespace Pumukit\CoreBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use JMS\Serializer\Serializer;
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
    public function testAction(Request $request): Response
    {
        /** @var DocumentManager */
        $dm = $this->get('doctrine_mongodb.odm.document_manager');

        $mmobjRepo = $dm->getRepository(MultimediaObject::class);

        $data = $mmobjRepo->createQueryBuilder()->distinct('_id')->getQuery()->execute();

        /** @var Serializer */
        $serializer = $this->get('jms_serializer');

        $requestFormat = ($request->getRequestFormat()) ?: 'html';

        $response = $serializer->serialize($data, $requestFormat);

        return new Response($response);
    }
}
