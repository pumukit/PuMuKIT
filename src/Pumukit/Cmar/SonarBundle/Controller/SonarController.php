<?php

namespace Pumukit\Cmar\SonarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/sonar")
 */
class SonarController extends Controller
{
    /**
     * @Route("/", name="sonar")
     * @Template()
     */
    public function indexAction()
    {
      $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
      $breadcrumbs->addList("Sonar", "sonar");
      return array();
    }  

    /**
     * @Route("/procesosignado", name="sonar_procesosignado")
     * @Template()
     */
    public function procesosignadoAction()
    {
      $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
      $breadcrumbs->addList("Sonar", "sonar");
      $breadcrumbs->add("The process of signing", "sonar_procesosignado");
      return array();
    }  

    /**
     * @Route("/sonar", name="sonar_sonar")
     * @Template()
     */
    public function sonarAction()
    {
      $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
      $breadcrumbs->addList("Sonar", "sonar");
      $breadcrumbs->add("What is sonar?", "sonar_sonar");    
      return array();
    }
}
