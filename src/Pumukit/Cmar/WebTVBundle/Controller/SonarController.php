<?php

namespace Pumukit\Cmar\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/sonar")
 */
class SonarController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
      return array();
    }  

    /**
     * @Route("/procesosignado")
     * @Template()
     */
    public function procesosignadoAction()
    {
      return array();
    }  

    /**
     * @Route("/sonar")
     * @Template()
     */
    public function sonarAction()
    {
      return array();
    }  
}
