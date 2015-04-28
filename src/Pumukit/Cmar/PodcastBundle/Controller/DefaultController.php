<?php

namespace Pumukit\Cmar\PodcastBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("/podcast/conferencevideo.xml", defaults={"_format": "xml"})
     * @Template("PumukitCmarPodcastBundle:Default:index.xml.twig")
     */
    public function videoAction()
    {
	    $mmObjRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $multimediaObjects = $mmObjRepo->findBy(array('tracks.only_audio' => false));
        return array('multimediaObjects' => $multimediaObjects);
    }


    /**
     * @Route("/podcast/conferenceaudio.xml", defaults={"_format": "xml"})
     * @Template("PumukitCmarPodcastBundle:Default:index.xml.twig")
     */
    public function audioAction()
    {
	    $mmObjRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $multimediaObjects = $mmObjRepo->findBy(array('tracks.only_false' => true));
        return array('multimediaObjects' => $multimediaObjects);
    }
}
