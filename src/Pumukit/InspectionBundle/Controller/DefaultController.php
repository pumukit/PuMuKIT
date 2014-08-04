<?php

namespace Pumukit\InspectionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller
{
    /**
     * @Route("/admin/inspect")
     */
    public function indexAction(Request $request)
    {

        $inspector = $this->get('pumukit.inspection');
        $duration = $inspector->getDuration('/var/www/Pumukit2/src/Pumukit/InspectionBundle/Resources/CAMERA.mp4');
        // usa JsonEncoder de php
        return new JsonResponse(array('duration' => $duration));
    }
}
