<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class IngesterController extends Controller
{
    /**
     * @Route("/ingester")
     * @Template()
     */
    public function indexAction()
    {
      return array();
    }
}