<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\EmbeddedEventSessionService;
use Pumukit\WebTVBundle\Services\BreadcrumbsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class EventController extends AbstractController implements WebTVControllerInterface
{
    protected $documentManager;
    protected $breadcrumbsService;
    protected $translator;
    protected $pumukitNewAdminAdvanceLiveEventCreateDefaultPic;
    protected $eventSessionService;
    protected $paginationService;
    protected $columnsObjsEvent;
    protected $pumukitLiveTwitterEnable;
    protected $pumukitNewAdminAdvanceLiveEvent;

    public function __construct(
        DocumentManager $documentManager,
        BreadcrumbsService $breadcrumbsService,
        TranslatorInterface $translator,
        PaginationService $paginationService,
        EmbeddedEventSessionService $eventSessionService,
        string $pumukitNewAdminAdvanceLiveEventCreateDefaultPic,
        int $columnsObjsEvent,
        bool $pumukitLiveTwitterEnable,
        bool $pumukitNewAdminAdvanceLiveEvent
    ) {
        $this->documentManager = $documentManager;
        $this->breadcrumbsService = $breadcrumbsService;
        $this->translator = $translator;
        $this->pumukitNewAdminAdvanceLiveEventCreateDefaultPic = $pumukitNewAdminAdvanceLiveEventCreateDefaultPic;
        $this->eventSessionService = $eventSessionService;
        $this->columnsObjsEvent = $columnsObjsEvent;
        $this->paginationService = $paginationService;
        $this->pumukitLiveTwitterEnable = $pumukitLiveTwitterEnable;
        $this->pumukitNewAdminAdvanceLiveEvent = $pumukitNewAdminAdvanceLiveEvent;
    }

    public function advanceLiveEventMenuAction(): Response
    {
        if ($this->pumukitNewAdminAdvanceLiveEvent) {
            return new Response($this->renderView('@PumukitWebTV/Menu/advance_event_link.html.twig'));
        }

        return new Response();
    }

    /**
     * @Route ("/events/", defaults={"filter"=false}, name="pumukit_webtv_events")
     */
    public function indexAction(Request $request): Response
    {
        $advanceEvents = $this->checkAdvanceEvents();
        if (!$advanceEvents) {
            return $this->render('@PumukitWebTV/Index/404notfound.html.twig');
        }

        $this->updateBreadcrumbs($this->translator->trans('Live events'), 'pumukit_webtv_events');

        $eventsNow = $this->eventSessionService->findEventsNow();
        $eventsToday = $this->eventSessionService->findEventsToday();
        $eventsToday = $this->getEventsTodayNextSession($eventsNow, $eventsToday);
        $eventsFuture = $this->eventSessionService->findNextEvents();

        $page = $request->query->get('page', 1);

        $maxPerPage = $this->columnsObjsEvent * 3;

        $pager = $this->paginationService->createArrayAdapter($eventsFuture, $page, $maxPerPage);

        return $this->render('@PumukitWebTV/Live/template.html.twig', [
            'eventsToday' => $eventsToday,
            'eventsNow' => $eventsNow,
            'eventsFuture' => $pager,
            'defaultPic' => $this->pumukitNewAdminAdvanceLiveEventCreateDefaultPic,
            'objectByCol' => $this->columnsObjsEvent,
            'show_info' => true,
            'show_description' => false,
        ]);
    }

    public function liveListAction(): Response
    {
        $events = $this->eventSessionService->findEventsNow();

        return $this->render('@PumukitWebTV/Live/Advance/livelist.html.twig', [
            'events' => $events,
            'defaultPic' => $this->pumukitNewAdminAdvanceLiveEventCreateDefaultPic,
        ]);
    }

    /**
     * @Route("/event/next/session/{id}", name="pumukit_webtv_next_session_event")
     *
     * @param mixed $id
     */
    public function nextSessionListAction($id): Response
    {
        $embeddedEventSessionService = $this->eventSessionService;

        $criteria = [
            '_id' => new ObjectId($id),
        ];
        $events = $embeddedEventSessionService->findNextSessions($criteria, 0, true);

        return $this->render('@PumukitWebTV/Live/Advance/nextsessionlist.html.twig', [
            'events' => $events,
            'sessionlist' => true,
            'defaultPic' => $this->pumukitNewAdminAdvanceLiveEventCreateDefaultPic,
        ]);
    }

    /**
     * @Route("/event/twitter/{id}", name="pumukit_webtv_event_twitter")
     */
    public function twitterAction(string $id)
    {
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->find($id);

        return $this->render('@PumukitWebTV/Live/Advance/twitter.html.twig', [
            'multimediaObject' => $multimediaObject,
            'enable_twitter' => $this->pumukitLiveTwitterEnable,
        ]);
    }

    private function updateBreadcrumbs(string $title, string $routeName, array $routeParameters = []): void
    {
        $this->breadcrumbsService->addList($title, $routeName, $routeParameters);
    }

    private function getEventsTodayNextSession(array $eventsNow, array $eventsToday): array
    {
        $now = array_map(
            static function ($e) {
                return $e['_id'];
            },
            $eventsNow
        );

        $result = [];
        foreach ($eventsToday as $event) {
            if (in_array($event['_id'], $now)) {
                continue;
            }
            $result[] = $event;
        }

        return $result;
    }

    private function checkAdvanceEvents(): bool
    {
        return $this->pumukitNewAdminAdvanceLiveEvent;
    }
}
