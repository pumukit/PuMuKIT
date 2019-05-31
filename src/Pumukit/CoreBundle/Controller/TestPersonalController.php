<?php

namespace Pumukit\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestPersonalController extends Controller implements PersonalControllerInterface
{
    /**
     * @Route("/test/personalfilter.{_format}", name="pumukit_core_tests_personalfilter", defaults={"_format":"json"})
     */
    public function testAction(Request $request)
    {
        $mmobjRepo = $this
          ->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $data = $mmobjRepo->createQueryBuilder()->distinct('_id')->getQuery()->execute();
        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($data, $request->getRequestFormat());

        return new Response($response);
    }
}
