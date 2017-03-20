<?php

namespace Pumukit\WebTVBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Pumukit\SchemaBundle\Document\Track;

class MultimediaObjectController extends PlayerController implements WebTVController
{
    /**
     * @Route("/video/{id}", name="pumukit_webtv_multimediaobject_index" )
     * @Template("PumukitWebTVBundle:MultimediaObject:index.html.twig")
     */
    public function indexAction(MultimediaObject $multimediaObject, Request $request)
    {
        $response = $this->preExecute($multimediaObject, $request);
        if ($response instanceof Response) {
            return $response;
        }

        $track = null;

        if($request->query->has('track_id')) {
            $track = $multimediaObject->getTrackById($request->query->get('track_id'));

            if (!$track) {
                throw $this->createNotFoundException();
            }

            if ($track->containsTag('download')) {
                return $this->redirect($track->getUrl());
            }
        }

        $this->updateBreadcrumbs($multimediaObject);

        $editorChapters = $this->getChapterMarks($multimediaObject);

        return array(
            'autostart' => $request->query->get('autostart', 'true'),
            'intro' => $this->getIntro($request->query->get('intro')),
            'multimediaObject' => $multimediaObject,
            'track' => $track,
            'editor_chapters' => $editorChapters,
        );
    }

    /**
     * @Route("/iframe/{id}", name="pumukit_webtv_multimediaobject_iframe" )
     */
    public function iframeAction(MultimediaObject $multimediaObject, Request $request)
    {
        return $this->forward('PumukitBasePlayerBundle:BasePlayer:index', array('request' => $request, 'multimediaObject' => $multimediaObject));
    }

    /**
     * @Route("/video/magic/{secret}", name="pumukit_webtv_multimediaobject_magicindex", defaults={"show_hide": true})
     * @Template("PumukitWebTVBundle:MultimediaObject:index.html.twig")
     */
    public function magicIndexAction(MultimediaObject $multimediaObject, Request $request)
    {
        $mmobjService = $this->get('pumukitschema.multimedia_object');
        if ($mmobjService->isPublished($multimediaObject, 'PUCHWEBTV')) {
            if ($mmobjService->hasPlayableResource($multimediaObject) && $multimediaObject->isPublicEmbeddedBroadcast()) {
                return $this->redirect($this->generateUrl('pumukit_webtv_multimediaobject_index', array('id' => $multimediaObject->getId())));
            }
        } elseif (($multimediaObject->getStatus() != MultimediaObject::STATUS_PUBLISHED
                 && $multimediaObject->getStatus() != MultimediaObject::STATUS_HIDE
                 ) || !$multimediaObject->containsTagWithCod('PUCHWEBTV')) {
            return $this->render('PumukitWebTVBundle:Index:404notfound.html.twig');
        }

        $response = $this->preExecute($multimediaObject, $request, true);
        if ($response instanceof Response) {
            return $response;
        }

        $request->attributes->set('noindex', true);

        $track = $request->query->has('track_id') ?
               $multimediaObject->getTrackById($request->query->get('track_id')) :
               $multimediaObject->getDisplayTrack();

        if ($track && $track->containsTag('download')) {
            return $this->redirect($track->getUrl());
        }

        $this->updateBreadcrumbs($multimediaObject);

        return array('autostart' => $request->query->get('autostart', 'true'),
                     'intro' => $this->getIntro($request->query->get('intro')),
                     'multimediaObject' => $multimediaObject,
                     'track' => $track,
                     'magic_url' => true, );
    }

    /**
     * @Route("/iframe/magic/{secret}", name="pumukit_webtv_multimediaobject_magiciframe", defaults={"show_hide": true})
     */
    public function magicIframeAction(MultimediaObject $multimediaObject, Request $request)
    {
        if ($multimediaObject->getStatus() === MultimediaObject::STATUS_HIDDEN) {
            return $this->forward('PumukitBasePlayerBundle:BasePlayer:magic', array('request' => $request, 'multimediaObject' => $multimediaObject));
        } else {
            return $this->redirect($this->generateUrl('pumukit_webtv_multimediaobject_iframe', array('id' => $multimediaObject->getId())));
        }
    }

    /**
     * @Template()
     */
    public function seriesAction(MultimediaObject $multimediaObject)
    {
        $series = $multimediaObject->getSeries();

        $mmobjRepo = $this
          ->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitSchemaBundle:MultimediaObject');

        $limit = $this->container->getParameter('limit_objs_player_series');

        $multimediaObjects = $mmobjRepo->findWithStatus($series, array(MultimediaObject::STATUS_PUBLISHED), $limit);

        return array('series' => $series,
                     'multimediaObjects' => $multimediaObjects,
        );
    }

    /**
     * @Template()
     */
    public function relatedAction(MultimediaObject $multimediaObject)
    {
        $mmobjRepo = $this
        ->get('doctrine_mongodb.odm.document_manager')
        ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $relatedMms = $mmobjRepo->findRelatedMultimediaObjects($multimediaObject);

        return array('multimediaObjects' => $relatedMms);
    }

    public function preExecute(MultimediaObject $multimediaObject, Request $request, $secret = false)
    {
        if ($opencasturl = $multimediaObject->getProperty('opencasturl')) {
            if ($secret) {
                return $this->forward('PumukitWebTVBundle:Opencast:magic', array('request' => $request, 'multimediaObject' => $multimediaObject));
            } else {
                return $this->forward('PumukitWebTVBundle:Opencast:index', array('request' => $request, 'multimediaObject' => $multimediaObject));
            }
        }
    }
}
