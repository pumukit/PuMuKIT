<?php

namespace Pumukit\LiveBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\LiveBundle\Document\Live;

class DefaultController extends Controller
{
    /**
     * @Route("/live/{id}"))
     * @Template("PumukitLiveBundle:Default:index.html.twig")
     */
    public function indexAction(Live $live)
    {
        $this->updateBreadcrumbs($live->getName(), "pumukit_live_default_index", array("id" => $live->getId()));
        return array('live' => $live);
    }


    /**
     * @Route("/live", name="pumukit_live")
     * @Template("PumukitLiveBundle:Default:index.html.twig")
     */
    public function defaultAction()
    {      
        $repo = $this
          ->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitLiveBundle:Live');
        $live = $repo->findOneBy(array());

        if(!$live)
          throw $this->createNotFoundException('The live channel does not exist');
        
        $this->updateBreadcrumbs($live->getName(), "pumukit_live", array("id" => $live->getId()));
        
        return array('live' => $live);
    }


    private function updateBreadcrumbs($title, $routeName, array $routeParameters = array())
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addList($title, $routeName, $routeParameters);
    }
}
