<?php

declare(strict_types=1);

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
use Pumukit\WebTVBundle\PumukitWebTVBundle;
use Pumukit\WebTVBundle\Services\BreadcrumbsService;
use Pumukit\WebTVBundle\Services\ChapterMarkService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MultimediaObjectController extends AbstractController implements WebTVControllerInterface
{
    protected $chapterMarksService;
    protected $introService;
    protected $playerService;
    protected $multimediaObjectService;
    protected $documentManager;
    protected $requestStack;
    protected $embeddedBroadcastService;
    protected $breadcrumbsService;
    protected $eventDispatcher;
    protected $limitObjsPlayerSeries;
    protected $pumukitFullMagicUrl;
    protected $cinemaMode;

    public function __construct(
        ChapterMarkService $chapterMarksService,
        IntroService $introService,
        PlayerService $playerService,
        MultimediaObjectService $multimediaObjectService,
        DocumentManager $documentManager,
        RequestStack $requestStack,
        EmbeddedBroadcastService $embeddedBroadcastService,
        BreadcrumbsService $breadcrumbsService,
        EventDispatcherInterface $dispatcher,
        $limitObjsPlayerSeries,
        $pumukitFullMagicUrl,
        $cinemaMode
    ) {
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
        $this->eventDispatcher = $dispatcher;
    }

    /**
     * @Route("/video/{id}", name="pumukit_webtv_multimediaobject_index")
     */
    public function indexAction(Request $request, MultimediaObject $multimediaObject)
    {
        $track = null;

        if ($request->query->has('track_id')) {
            $track = $multimediaObject->getTrackById($request->query->get('track_id'));

            if (!$track) {
                throw $this->createNotFoundException();
            }

            if ($track->tags()->containsTag('download')) {
                $url = $track->getUrl();
                $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?').'forcedl=1';

                return $this->redirect($url);
            }
        }

        if (!$track && !$multimediaObject->isExternalType()) {
            $event = new ViewedEvent($multimediaObject);
            $this->eventDispatcher->dispatch($event, BasePlayerEvents::MULTIMEDIAOBJECT_VIEW);
        }

        $this->updateBreadcrumbs($multimediaObject);
        $editorChapters = $this->chapterMarksService->getChapterMarks($multimediaObject);

        return $this->render('@PumukitWebTV/MultimediaObject/template.html.twig', [
            'autostart' => $request->query->get('autostart', 'true'),
            'intro' => $this->introService->getVideoIntroduction($multimediaObject, $request->query->getBoolean('intro')),
            'multimediaObject' => $multimediaObject,
            'track' => $track,
            'editor_chapters' => $editorChapters,
            'cinema_mode' => $this->cinemaMode,
        ]);
    }

    /**
     * @Route("/iframe/{id}", name="pumukit_webtv_multimediaobject_iframe" )
     */
    public function iframeAction(Request $request, MultimediaObject $multimediaObject): Response
    {
        $playerController = $this->playerService->getPublicControllerPlayer($multimediaObject);

        return $this->forward($playerController, ['request' => $request, 'multimediaObject' => $multimediaObject]);
    }

    /**
     * @Route("/video/magic/{secret}", name="pumukit_webtv_multimediaobject_magicindex", defaults={"show_hide"=true})
     */
    public function magicIndexAction(Request $request, MultimediaObject $multimediaObject)
    {
        if ($this->multimediaObjectService->isPublished($multimediaObject, PumukitWebTVBundle::WEB_TV_TAG)) {
            if ($this->multimediaObjectService->hasPlayableResource($multimediaObject) && $multimediaObject->isPublicEmbeddedBroadcast()) {
                return $this->redirectToRoute('pumukit_webtv_multimediaobject_index', ['id' => $multimediaObject->getId()]);
            }
        } elseif ((MultimediaObject::STATUS_PUBLISHED != $multimediaObject->getStatus() && MultimediaObject::STATUS_HIDDEN != $multimediaObject->getStatus()) || !$multimediaObject->containsTagWithCod(PumukitWebTVBundle::WEB_TV_TAG)) {
            return $this->render('@PumukitWebTV/Index/404notfound.html.twig');
        }

        $request->attributes->set('noindex', true);

        $track = null;

        if ($request->query->has('track_id')) {
            $track = $multimediaObject->getTrackById($request->query->get('track_id'));

            if (!$track) {
                throw $this->createNotFoundException();
            }

            if ($track->tags()->containsTag('download')) {
                $url = $track->getUrl();
                $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?').'forcedl=1';

                return $this->redirect($url);
            }
        }

        $this->updateBreadcrumbs($multimediaObject);
        $editorChapters = $this->chapterMarksService->getChapterMarks($multimediaObject);

        return $this->render('@PumukitWebTV/MultimediaObject/template.html.twig', [
            'autostart' => $request->query->get('autostart', 'true'),
            'intro' => $this->introService->getVideoIntroduction($multimediaObject, $request->query->getBoolean('intro')),
            'multimediaObject' => $multimediaObject,
            'track' => $track,
            'magic_url' => true,
            'editor_chapters' => $editorChapters,
            'cinema_mode' => $this->cinemaMode,
            'fullMagicUrl' => $this->getMagicUrlConfiguration(),
        ]);
    }

    /**
     * @Route("/iframe/magic/{secret}", name="pumukit_webtv_multimediaobject_magiciframe", defaults={"show_hide"=true})
     */
    public function magicIframeAction(Request $request, MultimediaObject $multimediaObject): Response
    {
        $playerController = $this->playerService->getMagicControllerPlayer($multimediaObject);

        return $this->forward($playerController, ['request' => $request, 'multimediaObject' => $multimediaObject]);
    }

    public function seriesAction(Request $request, MultimediaObject $multimediaObject): Response
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
            if ($referer) {
                $fromSecret = 0 === strpos($referer, $secretSeriesUrl);
            }
        }

        $relatedLink = false;
        if ($referer) {
            $relatedLink = strpos($referer, 'magic');
        }

        $multimediaObjectMagicUrl = $request->get('magicUrl', false);

        $showMagicUrl = ($fromSecret || $relatedLink || $multimediaObjectMagicUrl);
        $fullMagicUrl = $this->getMagicUrlConfiguration();
        $status = ($showMagicUrl && $fullMagicUrl) ? [MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_HIDDEN] : [MultimediaObject::STATUS_PUBLISHED];

        $multimediaObjects = $this->documentManager->getRepository(MultimediaObject::class)->findBySeriesByTagCodAndStatusWithLimit(
            $series,
            PumukitWebTVBundle::WEB_TV_TAG,
            $status,
            $this->limitObjsPlayerSeries
        );

        return $this->render('@PumukitWebTV/MultimediaObject/template_series.html.twig', [
            'series' => $series,
            'multimediaObjects' => $multimediaObjects,
            'showMagicUrl' => $showMagicUrl,
            'fullMagicUrl' => $fullMagicUrl,
        ]);
    }

    public function relatedAction(MultimediaObject $multimediaObject): Response
    {
        $relatedMms = $this->documentManager->getRepository(MultimediaObject::class)->findRelatedMultimediaObjects($multimediaObject);

        return $this->render('@PumukitWebTV/MultimediaObject/template_related.html.twig', ['multimediaObjects' => $relatedMms]);
    }

    /**
     * @Route("/video/{id}/info", name="pumukit_webtv_multimediaobject_info" )
     */
    public function multimediaInfoAction(Request $request, MultimediaObject $multimediaObject): Response
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

        return $this->render(
            '@PumukitWebTV/MultimediaObject/template_info.html.twig',
            [
                'multimediaObject' => $multimediaObject,
                'editor_chapters' => $editorChapters,
                'showDownloads' => $showDownloads,
                'isMagicRoute' => $isMagicRoute,
                'fullMagicUrl' => $fullMagicUrl,
                'url' => $request->attributes->get('url') ?: null,
                'urlIframe' => $request->attributes->get('urlIframe') ?: null,
            ]
        );
    }

    private function getMagicUrlConfiguration()
    {
        return $this->pumukitFullMagicUrl;
    }

    private function updateBreadcrumbs(MultimediaObject $multimediaObject): void
    {
        $this->breadcrumbsService->addMultimediaObject($multimediaObject);
    }
}
