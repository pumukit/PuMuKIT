<?php

namespace Pumukit\CoreBundle\Controller;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestWebTVControllerInterface extends Controller implements WebTVControllerInterface
{
    /**
     * @Route("/test/webtvfilter.{_format}", name="pumukit_core_tests_webtvfilter", defaults={"_format":"json"})
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
