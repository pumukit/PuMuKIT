<?php

namespace Pumukit\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class TestPersonalController extends Controller implements PersonalController
{
    /**
     * @Route("/test/personalfilter.{_format}", name="pumukit_core_tests_personalfilter", defaults={"_format":"json"})
     * @Template()
     */
    public function testAction(Request $request)
    {
        $mmobjRepo = $this
          ->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $data = $mmobjRepo->createQueryBuilder()->distinct('_id')->getQuery()->execute();
        $serializer = $this->get('serializer');
        $response = $serializer->serialize($data, $request->getRequestFormat());

        return new Response($response);

        return new Response('haha');
    }
}
