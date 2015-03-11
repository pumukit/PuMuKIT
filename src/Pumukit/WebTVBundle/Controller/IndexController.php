<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class IndexController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
    	$series_repository = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');
    	$num_series = count($series_repository->findall());

        $repository = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');

        $multimediaObjects_sorted_by_numview = $repository->findBy(array(), array('numview' => -1));
        $multimediaObjects_sorted_by_numview = array_slice($multimediaObjects_sorted_by_numview, 0, 3);

        $multimediaObjects_sorted_by_public_date = $repository->findBy(array(), array('public_date' => -1));
        $multimediaObjects_sorted_by_public_date = array_slice($multimediaObjects_sorted_by_public_date, 0, 3);

        return array('num_series' => $num_series, 'multimediaObjects_sorted_by_numview' => $multimediaObjects_sorted_by_numview, 
            'multimediaObjects_sorted_by_public_date' => $multimediaObjects_sorted_by_public_date);
    }
}
