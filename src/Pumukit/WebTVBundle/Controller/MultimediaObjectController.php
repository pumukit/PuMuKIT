<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class MultimediaObjectController.
 */
class MultimediaObjectController extends Controller implements WebTVControllerInterface
{
    /**
     * @Route("/video/{id}", name="pumukit_webtv_multimediaobject_index" )
     * @Template("PumukitWebTVBundle:MultimediaObject:template.html.twig")
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @throws \MongoException
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexAction(MultimediaObject $multimediaObject, Request $request)
    {
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

        if (!$track && $multimediaObject->getProperty('externalplayer')) {
            $event = new ViewedEvent($multimediaObject);
            $this->get('event_dispatcher')->dispatch(BasePlayerEvents::MULTIMEDIAOBJECT_VIEW, $event);
        }

        $this->updateBreadcrumbs($multimediaObject);

        $editorChapters = $this->get('pumukit_web_tv.chapter_marks_service')->getChapterMarks($multimediaObject);

        return [
            'autostart' => $request->query->get('autostart', 'true'),
            'intro' => $this->get('pumukit_baseplayer.intro')->getIntroForMultimediaObject(
                $request->query->get('intro'),
                $multimediaObject->getProperty('intro')
            ),
            'multimediaObject' => $multimediaObject,
            'track' => $track,
            'editor_chapters' => $editorChapters,
            'cinema_mode' => $this->getParameter('pumukit_web_tv.cinema_mode'),
        ];
    }

    /**
     * @Route("/iframe/{id}", name="pumukit_webtv_multimediaobject_iframe" )
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return Response
     */
    public function iframeAction(MultimediaObject $multimediaObject, Request $request)
    {
        $playerController = $this->get('pumukit_baseplayer.player_service')->getPublicControllerPlayer($multimediaObject);

        return $this->forward($playerController, ['request' => $request, 'multimediaObject' => $multimediaObject]);
    }

    /**
     * @Route("/video/magic/{secret}", name="pumukit_webtv_multimediaobject_magicindex", defaults={"show_hide": true})
     * @Template("PumukitWebTVBundle:MultimediaObject:template.html.twig")
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @throws \MongoException
     *
     * @return array|Response|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function magicIndexAction(MultimediaObject $multimediaObject, Request $request)
    {
        $mmobjService = $this->get('pumukitschema.multimedia_object');
        if ($mmobjService->isPublished($multimediaObject, 'PUCHWEBTV')) {
            if ($mmobjService->hasPlayableResource($multimediaObject) && $multimediaObject->isPublicEmbeddedBroadcast()) {
                return $this->redirect($this->generateUrl('pumukit_webtv_multimediaobject_index', ['id' => $multimediaObject->getId()]));
            }
        } elseif ((
            MultimediaObject::STATUS_PUBLISHED != $multimediaObject->getStatus()
                && MultimediaObject::STATUS_HIDDEN != $multimediaObject->getStatus()
            )
            || !$multimediaObject->containsTagWithCod('PUCHWEBTV')) {
            return $this->render('PumukitWebTVBundle:Index:404notfound.html.twig');
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

        $editorChapters = $this->get('pumukit_web_tv.chapter_marks_service')->getChapterMarks($multimediaObject);

        return [
            'autostart' => $request->query->get('autostart', 'true'),
            'intro' => $this->get('pumukit_baseplayer.intro')->getIntroForMultimediaObject(
                $request->query->get('intro'),
                $multimediaObject->getProperty('intro')
            ),
            'multimediaObject' => $multimediaObject,
            'track' => $track,
            'magic_url' => true,
            'editor_chapters' => $editorChapters,
            'cinema_mode' => $this->getParameter('pumukit_web_tv.cinema_mode'),
            'fullMagicUrl' => $this->getMagicUrlConfiguration(),
        ];
    }

    /**
     * @Route("/iframe/magic/{secret}", name="pumukit_webtv_multimediaobject_magiciframe", defaults={"show_hide": true})
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return Response
     */
    public function magicIframeAction(MultimediaObject $multimediaObject, Request $request)
    {
        $playerController = $this->get('pumukit_baseplayer.player_service')->getMagicControllerPlayer($multimediaObject);

        return $this->forward($playerController, ['request' => $request, 'multimediaObject' => $multimediaObject]);
    }

    /**
     * @Template("PumukitWebTVBundle:MultimediaObject:template_series.html.twig")
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return array
     */
    public function seriesAction(MultimediaObject $multimediaObject, Request $request)
    {
        $series = $multimediaObject->getSeries();

        $mmobjRepo = $this
            ->get('doctrine_mongodb.odm.document_manager')
            ->getRepository(MultimediaObject::class)
        ;

        $limit = $this->container->getParameter('limit_objs_player_series');

        $referer = $request->headers->get('referer');
        $fromSecret = false;
        if (!$series->isHide() && $series->getSecret()) {
            $secretSeriesUrl = $this->generateUrl(
                'pumukit_webtv_series_magicindex',
                ['secret' => $series->getSecret()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $fromSecret = 0 === strpos($referer, $secretSeriesUrl);
        }

        $relatedLink = strpos($referer, 'magic');
        $multimediaObjectMagicUrl = $request->get('magicUrl', false);

        $showMagicUrl = ($fromSecret || $relatedLink || $multimediaObjectMagicUrl);
        $fullMagicUrl = $this->getMagicUrlConfiguration();
        $status = ($showMagicUrl && $fullMagicUrl) ?
            [MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_HIDDEN] :
            [MultimediaObject::STATUS_PUBLISHED];

        $multimediaObjects = $mmobjRepo->findWithStatus($series, $status, $limit);

        return [
            'series' => $series,
            'multimediaObjects' => $multimediaObjects,
            'showMagicUrl' => $showMagicUrl,
            'fullMagicUrl' => $fullMagicUrl,
        ];
    }

    /**
     * @Template("PumukitWebTVBundle:MultimediaObject:template_related.html.twig")
     *
     * @param MultimediaObject $multimediaObject
     *
     * @return array
     */
    public function relatedAction(MultimediaObject $multimediaObject)
    {
        $mmobjRepo = $this
            ->get('doctrine_mongodb.odm.document_manager')
            ->getRepository(MultimediaObject::class)
        ;
        $relatedMms = $mmobjRepo->findRelatedMultimediaObjects($multimediaObject);

        return ['multimediaObjects' => $relatedMms];
    }

    /**
     * @Route("/video/{id}/info", name="pumukit_webtv_multimediaobject_info" )
     * @Template("PumukitWebTVBundle:MultimediaObject:template_info.html.twig")
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @throws \MongoException
     *
     * @return array
     */
    public function multimediaInfoAction(MultimediaObject $multimediaObject, Request $request)
    {
        $requestRoute = $this->container->get('request_stack')->getMasterRequest()->get('_route');
        $isMagicRoute = false;
        if (false !== strpos($requestRoute, 'magic')) {
            $isMagicRoute = true;
        }

        $embeddedBroadcastService = $this->get('pumukitschema.embeddedbroadcast');
        $password = $request->get('broadcast_password');
        $showDownloads = true;
        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($multimediaObject, $this->getUser(), $password);
        if ($response instanceof Response) {
            $showDownloads = false;
        }
        $editorChapters = $this->get('pumukit_web_tv.chapter_marks_service')->getChapterMarks($multimediaObject);

        $fullMagicUrl = $this->getMagicUrlConfiguration();

        return [
            'multimediaObject' => $multimediaObject,
            'editor_chapters' => $editorChapters,
            'showDownloads' => $showDownloads,
            'isMagicRoute' => $isMagicRoute,
            'fullMagicUrl' => $fullMagicUrl,
        ];
    }

    /**
     * @return bool
     */
    private function getMagicUrlConfiguration()
    {
        return $this->container->getParameter('pumukit.full_magic_url');
    }

    /**
     * @param MultimediaObject $multimediaObject
     */
    private function updateBreadcrumbs(MultimediaObject $multimediaObject)
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addMultimediaObject($multimediaObject);
    }
}
