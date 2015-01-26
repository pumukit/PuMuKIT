<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class MultimediaObjectController extends Controller
{
    /**
     * @Route("/video/{id}")
     * @Template()
     */
    public function indexAction(MultimediaObject $multimediaObject)
    {

    	$Serie = $multimediaObject->getSeries();
    	$mmobjects = $Serie->getMultimediaObjects();
        return array('multimediaObject' => $multimediaObject, 'mmobjects' => $mmobjects);
    }
}