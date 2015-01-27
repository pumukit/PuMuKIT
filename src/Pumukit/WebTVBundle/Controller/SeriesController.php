<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class SeriesController extends Controller
{
    /**
     * @Route("/series/{id}")
     * @Template()
     */
    public function indexAction(Series $series)
    {
    	$mmobjs = $series->getMultimediaObjects();
    	
        return array('series' => $series, 'mmobjs' => $mmobjs);
    }
}