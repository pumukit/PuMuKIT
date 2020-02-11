<?php

namespace Pumukit\WebTVBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\BasePlayerBundle\Services\IntroService;
use Pumukit\BasePlayerBundle\Services\PlayerService;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\EmbeddedBroadcastService;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Pumukit\WebTVBundle\Services\BreadcrumbsService;
use Pumukit\WebTVBundle\Services\ChapterMarkService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MultimediaObjectController extends AbstractController implements WebTVControllerInterface
{
    /** @var EventDispatcher */
    private $eventDispatcher;

    /** @var ChapterMarkService */
    private $chapterMarksService;

    /** @var IntroService */
    private $introService;

    /** @var PlayerService */
    private $playerService;

    /** @var MultimediaObjectService */
    private $multimediaObjectService;

    /** @var DocumentManager */
    private $documentManager;

    /** @var RequestStack */
    private $requestStack;

    /** @var EmbeddedBroadcastService */
    private $embeddedBroadcastService;

    /** @var BreadcrumbsService */
    private $breadcrumbsService;
    private $limitObjsPlayerSeries;
    private $pumukitFullMagicUrl;
    private $cinemaMode;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ChapterMarkService $chapterMarksService,
        IntroService $introService,
        PlayerService $playerService,
        MultimediaObjectService $multimediaObjectService,
        DocumentManager $documentManager,
        RequestStack $requestStack,
        EmbeddedBroadcastService $embeddedBroadcastService,
        BreadcrumbsService $breadcrumbsService,
        $limitObjsPlayerSeries,
        $pumukitFullMagicUrl,
        $cinemaMode
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->chapterMarksService = $chapterMarksService;
        $this->introService = $introService;
        $this->playerService = $playerService;
        $this->multimediaObjectService = $multimediaObjectService;
        $this->documentManager = $documentManager;
        $this->requestStack = $requestStack;
        $this->embeddedBroadcastService = $embeddedBroadcastService;
        $this->breadcrumbsService = $breadcrumbsService;
        $this->limitObjsPlayerSeries = $limitObjsPlayerSeries;
        $this->pumukitFullMagicUrl = $pumukitFullMagicUrl;
        $this->cinemaMode = $cinemaMode;
    }

    /**
     * @Route("/video/{id}", name="pumukit_webtv_multimediaobject_index" )
     * @Template("PumukitWebTVBundle:MultimediaObject:template.html.twig")
     */
    public function indexAction(Request $request, MultimediaObject $multimediaObject)
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
            $this->eventDispatcher->dispatch($event, BasePlayerEvents::MULTIMEDIAOBJECT_VIEW);
        }

        $this->updateBreadcrumbs($multimediaObject);
        $editorChapters = $this->chapterMarksService->getChapterMarks($multimediaObject);

        return [
            'autostart' => $request->query->get('autostart', 'true'),
            'intro' => $this->introService->getVideoIntroduction($multimediaObject, $request->query->getBoolean('intro')),
            'multimediaObject' => $multimediaObject,
            'track' => $track,
            'editor_chapters' => $editorChapters,
            'cinema_mode' => $this->cinemaMode,
        ];
    }

    /**
     * @Route("/iframe/{id}", name="pumukit_webtv_multimediaobject_iframe" )
     */
    public function iframeAction(Request $request, MultimediaObject $multimediaObject)
    {
        $playerController = $this->playerService->getPublicControllerPlayer($multimediaObject);

        return $this->forward($playerController, ['request' => $request, 'multimediaObject' => $multimediaObject]);
    }

    /**
     * @Route("/video/magic/{secret}", name="pumukit_webtv_multimediaobject_magicindex", defaults={"show_hide": true})
     * @Template("PumukitWebTVBundle:MultimediaObject:template.html.twig")
     */
    public function magicIndexAction(Request $request, MultimediaObject $multimediaObject)
    {
        if ($this->multimediaObjectService->isPublished($multimediaObject, 'PUCHWEBTV')) {
            if ($this->multimediaObjectService->hasPlayableResource($multimediaObject) && $multimediaObject->isPublicEmbeddedBroadcast()) {
                return $this->redirect($this->generateUrl('pumukit_webtv_multimediaobject_index', ['id' => $multimediaObject->getId()]));
            }
        } elseif ((MultimediaObject::STATUS_PUBLISHED != $multimediaObject->getStatus() && MultimediaObject::STATUS_HIDDEN != $multimediaObject->getStatus()) || !$multimediaObject->containsTagWithCod('PUCHWEBTV')) {
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
        $editorChapters = $this->chapterMarksService->getChapterMarks($multimediaObject);

        return [
            'autostart' => $request->query->get('autostart', 'true'),
            'intro' => $this->introService->getVideoIntroduction($multimediaObject, $request->query->getBoolean('intro')),
            'multimediaObject' => $multimediaObject,
            'track' => $track,
            'magic_url' => true,
            'editor_chapters' => $editorChapters,
            'cinema_mode' => $this->cinemaMode,
            'fullMagicUrl' => $this->getMagicUrlConfiguration(),
        ];
    }

    /**
     * @Route("/iframe/magic/{secret}", name="pumukit_webtv_multimediaobject_magiciframe", defaults={"show_hide": true})
     */
    public function magicIframeAction(Request $request, MultimediaObject $multimediaObject)
    {
        $playerController = $this->playerService->getMagicControllerPlayer($multimediaObject);

        return $this->forward($playerController, ['request' => $request, 'multimediaObject' => $multimediaObject]);
    }

    /**
     * @Template("PumukitWebTVBundle:MultimediaObject:template_series.html.twig")
     */
    public function seriesAction(Request $request, MultimediaObject $multimediaObject)
    {
        $series = $multimediaObject->getSeries();

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
        $status = ($showMagicUrl && $fullMagicUrl) ? [MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_HIDDEN] : [MultimediaObject::STATUS_PUBLISHED];

        $multimediaObjects = $this->documentManager->getRepository(MultimediaObject::class)->findWithStatus(
            $series,
            $status,
            $this->limitObjsPlayerSeries
        );

        return [
            'series' => $series,
            'multimediaObjects' => $multimediaObjects,
            'showMagicUrl' => $showMagicUrl,
            'fullMagicUrl' => $fullMagicUrl,
        ];
    }

    /**
     * @Template("PumukitWebTVBundle:MultimediaObject:template_related.html.twig")
     */
    public function relatedAction(MultimediaObject $multimediaObject)
    {
        $relatedMms = $this->documentManager->getRepository(MultimediaObject::class)->findRelatedMultimediaObjects($multimediaObject);

        return ['multimediaObjects' => $relatedMms];
    }

    /**
     * @Route("/video/{id}/info", name="pumukit_webtv_multimediaobject_info" )
     * @Template("PumukitWebTVBundle:MultimediaObject:template_info.html.twig")
     */
    public function multimediaInfoAction(Request $request, MultimediaObject $multimediaObject)
    {
        $requestRoute = $this->requestStack->getMasterRequest()->get('_route');
        $isMagicRoute = false;
        if (false !== strpos($requestRoute, 'magic')) {
            $isMagicRoute = true;
        }

        $password = $request->get('broadcast_password');
        $showDownloads = true;
        $response = $this->embeddedBroadcastService->canUserPlayMultimediaObject($multimediaObject, $this->getUser(), $password);
        if ($response instanceof Response) {
            $showDownloads = false;
        }
        $editorChapters = $this->chapterMarksService->getChapterMarks($multimediaObject);
        $fullMagicUrl = $this->getMagicUrlConfiguration();

        return [
            'multimediaObject' => $multimediaObject,
            'editor_chapters' => $editorChapters,
            'showDownloads' => $showDownloads,
            'isMagicRoute' => $isMagicRoute,
            'fullMagicUrl' => $fullMagicUrl,
        ];
    }

    private function getMagicUrlConfiguration()
    {
        return $this->pumukitFullMagicUrl;
    }

    private function updateBreadcrumbs(MultimediaObject $multimediaObject)
    {
        $this->breadcrumbsService->addMultimediaObject($multimediaObject);
    }
}
