<?php

namespace Pumukit\WebTVBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        if ($request->query->has('track_id')) {
            $track = $multimediaObject->getTrackById($request->query->get('track_id'));

            if (!$track) {
                throw $this->createNotFoundException();
            }

            if ($track->containsTag('download')) {
                $url = $track->getUrl();
                $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?').'forcedl=1';

                return $this->redirect($url);
            }
        }

        $this->updateBreadcrumbs($multimediaObject);

        $editorChapters = $this->getChapterMarks($multimediaObject);

        return array(
            'autostart' => $request->query->get('autostart', 'true'),
            'intro' => $this->get('pumukit_baseplayer.intro')->getIntroForMultimediaObject($request->query->get('intro'), $multimediaObject->getProperty('intro')),
            'multimediaObject' => $multimediaObject,
            'track' => $track,
            'editor_chapters' => $editorChapters,
            'cinema_mode' => $this->getParameter('pumukit_web_tv.cinema_mode'),
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
        } elseif ((MultimediaObject::STATUS_PUBLISHED != $multimediaObject->getStatus()
                 && MultimediaObject::STATUS_HIDE != $multimediaObject->getStatus()
                 ) || !$multimediaObject->containsTagWithCod('PUCHWEBTV')) {
            return $this->render('PumukitWebTVBundle:Index:404notfound.html.twig');
        }

        $response = $this->preExecute($multimediaObject, $request, true);
        if ($response instanceof Response) {
            return $response;
        }

        $request->attributes->set('noindex', true);

        $track = null;

        if ($request->query->has('track_id')) {
            $track = $multimediaObject->getTrackById($request->query->get('track_id'));

            if (!$track) {
                throw $this->createNotFoundException();
            }

            if ($track->containsTag('download')) {
                $url = $track->getUrl();
                $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?').'forcedl=1';

                return $this->redirect($url);
            }
        }

        $this->updateBreadcrumbs($multimediaObject);

        $editorChapters = $this->getChapterMarks($multimediaObject);

        return array(
            'autostart' => $request->query->get('autostart', 'true'),
            'intro' => $this->get('pumukit_baseplayer.intro')->getIntroForMultimediaObject($request->query->get('intro'), $multimediaObject->getProperty('intro')),
            'multimediaObject' => $multimediaObject,
            'track' => $track,
            'magic_url' => true,
            'editor_chapters' => $editorChapters,
            'cinema_mode' => $this->getParameter('pumukit_web_tv.cinema_mode'),
        );
    }

    /**
     * @Route("/iframe/magic/{secret}", name="pumukit_webtv_multimediaobject_magiciframe", defaults={"show_hide": true})
     */
    public function magicIframeAction(MultimediaObject $multimediaObject, Request $request)
    {
        return $this->forward('PumukitBasePlayerBundle:BasePlayer:magic', array('request' => $request, 'multimediaObject' => $multimediaObject));
    }

    /**
     * @Template()
     */
    public function seriesAction(MultimediaObject $multimediaObject, Request $request)
    {
        $series = $multimediaObject->getSeries();

        $mmobjRepo = $this
          ->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitSchemaBundle:MultimediaObject');

        $limit = $this->container->getParameter('limit_objs_player_series');

        $referer = $request->headers->get('referer');
        $secretSeriesUrl = $this->generateUrl('pumukit_webtv_series_magicindex', array('secret' => $series->getSecret()), true);
        $fromSecret = 0 === strpos($referer, $secretSeriesUrl);
        $relatedLink = strpos($referer, 'magic');

        $status = ($fromSecret || $relatedLink) ?
                array(MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_HIDDEN) :
                array(MultimediaObject::STATUS_PUBLISHED);

        $multimediaObjects = $mmobjRepo->findWithStatus($series, $status, $limit);

        return array(
            'series' => $series,
            'multimediaObjects' => $multimediaObjects,
            'showMagicUrl' => ($fromSecret || $relatedLink),
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
        if ($multimediaObject->getProperty('opencasturl') && !$request->query->has('track_id')) {
            if ($secret) {
                return $this->forward('PumukitWebTVBundle:Opencast:magic', array('request' => $request, 'multimediaObject' => $multimediaObject));
            } else {
                return $this->forward('PumukitWebTVBundle:Opencast:index', array('request' => $request, 'multimediaObject' => $multimediaObject));
            }
        }
    }

    /**
     * @Route("/video/{id}/info", name="pumukit_webtv_multimediaobject_info" )
     * @Template("PumukitWebTVBundle:MultimediaObject:info.html.twig")
     */
    public function multimediaInfoAction(MultimediaObject $multimediaObject, Request $request)
    {
        $embeddedBroadcastService = $this->get('pumukitschema.embeddedbroadcast');
        $password = $request->get('broadcast_password');
        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($multimediaObject, $this->getUser(), $password);
        if ($response instanceof Response) {
            return $this->render('PumukitWebTVBundle:MultimediaObject:emptyinfo.html.twig');
        }
        $editorChapters = $this->getChapterMarks($multimediaObject);

        return array(
            'multimediaObject' => $multimediaObject,
            'editor_chapters' => $editorChapters,
        );
    }
}
