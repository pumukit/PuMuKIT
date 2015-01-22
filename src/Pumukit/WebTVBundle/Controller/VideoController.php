<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class VideoController extends Controller
{
    /**
     * @Route("/video/{id}")
     * @Template()
     */
    public function indexAction(MultimediaObject $multimediaObject)
    {
        return array('multimediaObject' => $multimediaObject);
    }
}