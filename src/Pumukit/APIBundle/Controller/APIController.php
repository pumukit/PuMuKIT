<?php

namespace Pumukit\APIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/api/media")
 */
class APIController extends Controller
{
    /**
     * @Route("/stats.{_format}", defaults={"_format"="json"}, requirements={"_format": "json"})
     */
    public function statsAction()
    {
        $mmRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $seriesRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:series');

        $counts = array('series' => $seriesRepo->countPublic(),
                        'mms' => $mmRepo->count(),
                        'hours' => bcdiv($mmRepo->countDuration(), 3600, 2), );

        return new JsonResponse($counts);
    }
}
