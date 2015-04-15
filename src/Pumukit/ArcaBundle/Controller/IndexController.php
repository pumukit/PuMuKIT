<?php

namespace Pumukit\ArcaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class IndexController extends Controller
{
    /**
     * @Route("/arca.xml", defaults={"_format": "xml"})
     * @Template()
     */
    public function indexAction()
    {
        return array('years' => array(2011, 2015));
    }

    /**
     * @Route("{year}/arca.xml", defaults={"_format": "xml"})
     * @Template()
     */
    public function listAction($year)
    {
	    $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');

        return array('multimediaObjects' => $repo->findAll());
    }
}
