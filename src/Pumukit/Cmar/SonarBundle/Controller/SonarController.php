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
      $this->updateBreadcrumbs("Sonar", "sonar");
      return array();
    }  

    /**
     * @Route("/procesosignado", name="sonar_procesosignado")
     * @Template()
     */
    public function procesosignadoAction()
    {
      $this->updateBreadcrumbs("The process of signing", "sonar_procesosignado");
      return array();
    }  

    /**
     * @Route("/sonar", name="sonar_sonar")
     * @Template()
     */
    public function sonarAction()
    {
      $this->updateBreadcrumbs("What is sonar?", "sonar_sonar");    
      return array();
    }

    private function updateBreadcrumbs($title, $routeName, array $routeParameters = array())
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addList($title, $routeName, $routeParameters);
    }
}
