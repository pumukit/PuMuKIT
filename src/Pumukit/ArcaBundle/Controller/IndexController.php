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
	    $repository_multimediaObjects = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');

        $start = new \DateTime($year . '/01/01');
        $end = new \DateTime($year . '/12/31');

        $in_range = array('$gte' => $start, '$lt' => $end);

        $multimediaObjects = $repository_multimediaObjects->findBy(array('record_date' => $in_range));

        return array('multimediaObjects' => $multimediaObjects);
    }
}
