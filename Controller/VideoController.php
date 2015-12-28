<?php

namespace Pumukit\VideoEditorBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Pumukit\SchemaBundle\Document\Track;

class VideoController
{
    /**
     * @Route("/video/{id}", name="pumukit_videoeditor_index" )
     * @Template()
     */
    public function indexAction(MultimediaObject $multimediaObject, Request $request)
    {
        $track = $request->query->has('track_id') ?
        $multimediaObject->getTrackById($request->query->get('track_id')) :
        $multimediaObject->getFilteredTrackWithTags(array('display'));

        if($track && $track->containsTag("download")) {       
            return $this->redirect($track->getUrl());
        }
        //ADD LOGIC TO CHECK IF VIDEO IS MULTISTREAM (opencast)
        //Then just return several tracks.
        $tracks = array($track);

        return array('multimediaObject' => $multimediaObject,
                     'tracks' => $tracks);
    }
}
