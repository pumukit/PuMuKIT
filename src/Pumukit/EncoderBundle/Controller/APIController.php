<?php

namespace Pumukit\EncoderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/admin/encoder/api")
 */
class APIController extends Controller
{
    /**
     * @Route("/profiles.{_format}", defaults={"_format"="json"}, requirements={"_format": "json"})
     */
    public function profilesAction()
    {
        $profiles = $this->get('pumukitencoder.profile')->getProfiles();
        return new JsonResponse($profiles);
    }

    /**
     * @Route("/cpus.{_format}", defaults={"_format"="json"}, requirements={"_format": "json"})
     */
    public function cpusAction()
    {
        $cpus = $this->get('pumukitencoder.cpu')->getCpus();
        return new JsonResponse($cpus);
    }    
}
