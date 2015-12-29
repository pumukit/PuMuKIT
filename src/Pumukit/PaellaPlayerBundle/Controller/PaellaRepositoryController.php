<?php

namespace Pumukit\PaellaPlayerBundle\Controller;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\VideoEditorBundle\Document\Annotation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class PaellaRepositoryController extends Controller
{
    /**
     * @Route("/paellarepository/{id}.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     * @Method("GET")
     */
    public function indexAction(MultimediaObject $mmobj, Request $request)
    {
        //TODO: Do the annotation getting using a service function.
        //$opencastAnnotationService = $this->container->get('video_editor.opencast_annotations');
        $serializer = $this->get('serializer');
        $picService = $this->get('pumukitschema.pic');
        $track = $mmobj->getFilteredTrackWithTags(array('display'));
        $pic = $picService->getFirstUrlPic($mmobj, true, false);

        $src = $this->getAbsoluteUrl($request, $track->getUrl());
        
        $data = array();
        $data['streams'] = array();
        $data['streams'][] = array('sources' => array('mp4' => array(array('src' => $src,
                                                                     'mimetype' => $track->getMimetype(),
                                                                     'res' => array('w' => 0, 'h' => 0)))),
                                   'preview' => $pic);

        
        $data['metadata'] = array('title' => $mmobj->getTitle(),
                                  'description' => $mmobj->getDescription(),
                                  'duration' => 0);

        $response = $serializer->serialize($data, $request->getRequestFormat());
        return new Response($response);
    }

    /**
     * Returns the absolute url from a given path or url
     */
    private function getAbsoluteUrl($request, $url) {
        if (false !== strpos($url, '://') || 0 === strpos($url, '//')) {
            return $url;
        }

        if ('' === $host = $request->getHost()) {
            return $url;
        }

        return $request->getSchemeAndHttpHost().$request->getBasePath().$url;
    }
}
