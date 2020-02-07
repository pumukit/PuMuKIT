<?php

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\NewAdminBundle\Form\Type\EmbeddedEventSessionType;
use Pumukit\NewAdminBundle\Form\Type\EventsType;
use Pumukit\NewAdminBundle\Form\Type\SeriesType;
use Pumukit\SchemaBundle\Document\EmbeddedEvent;
use Pumukit\SchemaBundle\Document\EmbeddedEventSession;
use Pumukit\SchemaBundle\Document\EmbeddedSocial;
use Pumukit\SchemaBundle\Document\Live;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Services\EmbeddedEventSessionService;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\MultimediaObjectPicService;
use Pumukit\SchemaBundle\Services\SeriesEventDispatcherService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_LIVE_EVENTS')")
 * @Route("liveevent/")
 */
class EventsController extends AbstractController implements NewAdminControllerInterface
{
    protected static $regex = '/^[0-9a-z]{24}$/';

    /** @var DocumentManager */
    protected $documentManager;
    /** @var TranslatorInterface */
    protected $translatorService;
    /** @var FactoryService */
    protected $factoryService;
    /** @var MultimediaObjectPicService */
    protected $multimediaObjectPicService;
    /** @var SeriesEventDispatcherService */
    protected $seriesDispatcher;
    /** @var PaginationService */
    protected $paginationService;
    /** @var EmbeddedEventSessionService */
    protected $eventsService;

    public function __construct(
        DocumentManager $documentManager,
        TranslatorInterface $translatorService,
        FactoryService $factoryService,
        MultimediaObjectPicService $multimediaObjectPicService,
        SeriesEventDispatcherService $seriesDispatcher,
        PaginationService $paginationService,
        EmbeddedEventSessionService $eventsService
    ) {
        $this->documentManager = $documentManager;
        $this->translatorService = $translatorService;
        $this->factoryService = $factoryService;
        $this->multimediaObjectPicService = $multimediaObjectPicService;
        $this->seriesDispatcher = $seriesDispatcher;
        $this->paginationService = $paginationService;
        $this->eventsService = $eventsService;
    }

    /**
     * @return array
     *
     * @Route("index/", name="pumukit_new_admin_live_event_index")
     * @Template("PumukitNewAdminBundle:LiveEvent:index.html.twig")
     */
    public function indexEventAction(Request $request)
    {
        if ($request->query->get('page')) {
            $this->get('session')->set('admin/live/event/page', $request->query->get('page'));
        }

        $aRoles = $this->documentManager->getRepository(Role::class)->findAll();
        $aPubChannel = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => 'PUBCHANNELS']);
        $aChannels = $this->documentManager->getRepository(Tag::class)->findBy(
            ['parent.$id' => new ObjectId($aPubChannel->getId())]
        );

        $statusPub = [
            MultimediaObject::STATUS_PUBLISHED => 'Published',
            MultimediaObject::STATUS_BLOCKED => 'Blocked',
            MultimediaObject::STATUS_HIDDEN => 'Hidden',
        ];

        $object = [];

        return [
            'object' => $object,
            'disable_pudenew' => !$this->getParameter('show_latest_with_pudenew'),
            'roles' => $aRoles,
            'statusPub' => $statusPub,
            'pubChannels' => $aChannels,
        ];
    }

    /**
     * @throws \Exception
     *
     * @return RedirectResponse
     *
     * @Route("create/", name="pumukit_new_admin_live_event_create")
     */
    public function createEventAction(Request $request)
    {
        $languages = $this->getParameter('pumukit.locales');

        $series = $request->request->get('seriesSuggest') ? $request->request->get('seriesSuggest') : false;

        $createSeries = false;
        if (!$series) {
            $series = $this->factoryService->createSeries($this->getUser());
            $this->documentManager->persist($series);
            $createSeries = true;
        } else {
            $series = $this->documentManager->getRepository(Series::class)->findOneBy(
                ['_id' => new ObjectId($series)]
            );
        }

        $multimediaObject = $this->factoryService->createMultimediaObject($series, true, $this->getUser());
        $multimediaObject->setType(MultimediaObject::TYPE_LIVE);

        if (!$createSeries) {
            $seriesPics = $series->getPics();
            if (count($seriesPics) > 0) {
                $eventPicSeriesDefault = $series->getPic();
                $this->multimediaObjectPicService->addPicUrl($multimediaObject, $eventPicSeriesDefault->getUrl(), false);
            } else {
                $eventPicSeriesDefault = $this->getParameter('pumukit_new_admin.advance_live_event_create_serie_pic');
                $this->multimediaObjectPicService->addPicUrl($multimediaObject, $eventPicSeriesDefault, false);
            }
        }

        // Create default event
        $event = new EmbeddedEvent();
        $event->setDate(new \DateTime());

        foreach ($languages as $language) {
            $event->setName($this->translatorService->trans('New'), $language);
            $event->setDescription('', $language);
        }

        $event->setCreateSerial(true);
        $this->documentManager->persist($event);

        $multimediaObject->setEmbeddedEvent($event);
        $this->documentManager->persist($multimediaObject);
        $this->documentManager->flush();

        $session = $this->get('session');
        $session->set('admin/live/event/id', $multimediaObject->getId());
        $this->get('session')->set('admin/live/event/page', 1);

        return $this->redirectToRoute('pumukit_new_admin_live_event_list');
    }

    /**
     * List events.
     *
     * @param string|null $type
     *
     * @return array
     *
     * @Route("list/event/{type}", name="pumukit_new_admin_live_event_list")
     * @Template("PumukitNewAdminBundle:LiveEvent:list.html.twig")
     */
    public function listEventAction(Request $request, $type = null)
    {
        $session = $this->get('session');
        $eventPicDefault = $this->getParameter('pumukit_new_admin.advance_live_event_create_default_pic');
        $page = ($this->get('session')->get('admin/live/event/page')) ?: ($request->query->get('page') ?: 1);

        $criteria['type'] = MultimediaObject::TYPE_LIVE;
        if ($type) {
            $date = new UTCDateTime();
            if ('now' === $type) {
                $criteria['embeddedEvent.embeddedEventSession'] = ['$elemMatch' => [
                    'start' => ['$lte' => $date],
                    'ends' => ['$gte' => $date],
                ]];
            } elseif ('today' === $type) {
                $dateStart = new \DateTime(date('Y-m-d'));
                $dateEnds = new \DateTime(date('Y-m-d 23:59:59'));
                $dateStart = new UTCDateTime($dateStart);
                $dateEnds = new UTCDateTime($dateEnds);
                $criteria['embeddedEvent.embeddedEventSession'] = ['$elemMatch' => [
                    'start' => ['$gte' => $dateStart],
                    'ends' => ['$lte' => $dateEnds],
                ]];
            } else {
                $criteria['embeddedEvent.embeddedEventSession.start'] = ['$gt' => $date];
            }
        } elseif ($request->query->has('criteria')) {
            $data = $request->query->get('criteria');
            $session->set('admin/live/event/dataForm', $data);
            if (!empty($data['name'])) {
                if (preg_match($this::regex, $data['name'])) {
                    $criteria['_id'] = new ObjectId($data['name']);
                } else {
                    $criteria['embeddedEvent.name.'.$request->getLocale()] = new Regex($data['name'], 'i');
                }
            }
            if ($data['date']['from'] && $data['date']['to']) {
                $start = strtotime($data['date']['from']) * 1000;
                $ends = strtotime($data['date']['to'].'23:59:59') * 1000;

                $criteria['embeddedEvent.embeddedEventSession'] = ['$elemMatch' => [
                    'start' => [
                        '$gte' => new UTCDateTime($start),
                    ],
                    'ends' => [
                        '$lte' => new UTCDateTime($ends),
                    ], ]];
            } else {
                if ($data['date']['from']) {
                    $date = strtotime($data['date']['from']) * 1000;
                    $criteria['embeddedEvent.embeddedEventSession.start'] = ['$gte' => new UTCDateTime($date)];
                }
                if ($data['date']['to']) {
                    $date = strtotime($data['date']['to']) * 1000;
                    $criteria['embeddedEvent.embeddedEventSession.ends'] = ['$lte' => new UTCDateTime($date)];
                }
            }
        } elseif ($session->has('admin/live/event/criteria')) {
            $criteria = $session->get('admin/live/event/criteria');
        }

        $session->set('admin/live/event/criteria', $criteria);
        $sortField = $session->get('admin/live/event/sort/field', '_id');
        $sortType = $session->get('admin/live/event/sort/type', 'desc');
        $session->set('admin/live/event/sort/field', $sortField);
        $session->set('admin/live/event/sort/type', $sortType);
        if ('embeddedEvent.embeddedEventSession.start' === $sortField) {
            $multimediaObjects = $this->documentManager->getRepository(MultimediaObject::class)->findBy($criteria);
            $multimediaObjects = $this->reorderMultimediaObjectsByNextNearSession($multimediaObjects, $sortType);
        } else {
            $multimediaObjects = $this->documentManager->getRepository(MultimediaObject::class)->findBy(
                $criteria,
                [$sortField => $sortType]
            );
        }

        $pager = $this->paginationService->createArrayAdapter($multimediaObjects, $page, 10);

        if ($pager->getNbResults() > 0) {
            $resetCache = true;
            foreach ($pager->getCurrentPageResults() as $result) {
                if ($session->get('admin/live/event/id') == $result->getId()) {
                    $resetCache = false;

                    break;
                }
            }
            if ($resetCache) {
                foreach ($pager->getCurrentPageResults() as $result) {
                    $session->set('admin/live/event/id', $result->getId());

                    break;
                }
            }
        } else {
            $session->remove('admin/live/event/id');
        }

        return ['multimediaObjects' => $pager, 'default_event_pic' => $eventPicDefault];
    }

    /**
     * @return JsonResponse
     *
     * @Route("add/sorting/", name="pumukit_new_admin_live_event_set_sorting")
     */
    public function addSessionSortingAction(Request $request)
    {
        $session = $this->get('session');

        if ($request->request->get('field')) {
            $field = $request->request->get('field');
            if ('embeddedEvent.name' === $request->request->get('field')) {
                $field = 'embeddedEvent.name.'.$request->getLocale();
            }
            if ($session->has('admin/live/event/sort/field') && $session->get('admin/live/event/sort/field') === $field) {
                $session->set('admin/live/event/sort/type', (('desc' == $session->get('admin/live/event/sort/type')) ? 'asc' : 'desc'));
            } else {
                $session->set('admin/live/event/sort/type', 'desc');
            }

            $session->set('admin/live/event/sort/field', $field);

            return new JsonResponse(['success']);
        }

        return new JsonResponse(['error']);
    }

    /**
     * @return JsonResponse
     *
     * @Route("remove/session/", name="pumukit_newadmin_live_events_reset_session")
     */
    public function removeCriteriaSessionAction()
    {
        $session = $this->get('session');
        $session->remove('admin/live/event/sort/field');
        $session->remove('admin/live/event/sort/type');
        $session->remove('admin/live/event/criteria');
        $session->remove('admin/live/event/dataForm');
        $session->remove('admin/live/event/id');
        $session->remove('admin/live/event/page');

        return new JsonResponse(['succcess']);
    }

    /**
     * Event options .
     *
     * @param string $type
     *
     * @return JsonResponse
     * @Route("list/options/{type}/{id}", name="pumukit_new_admin_live_event_options")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id":
     *                                     "id"}})
     * @Template("PumukitNewAdminBundle:LiveEvent:updatemenu.html.twig")
     */
    public function menuOptionsAction($type, MultimediaObject $multimediaObject)
    {
        $message = '';

        try {
            switch ($type) {
            case 'clone':
                $message = $this->cloneEvent($multimediaObject);

                break;
            case 'delete':
                $message = $this->deleteEvent($multimediaObject);
                $this->container->get('session')->set('admin/live/event/id', null);

                break;
            case 'deleteAll':
                $message = $this->deleteEventAndSeries($multimediaObject);
                $this->container->get('session')->set('admin/live/event/id', null);

                break;
            default:
                $message = 'Option not allowed';

                break;
            }
        } catch (\Exception $e) {
            return new JsonResponse(['status' => $e->getMessage()], 409);
        }

        return new JsonResponse(['status' => $this->translatorService->trans($message)]);
    }

    /**
     * @Route("delete/selected/", name="pumukit_new_admin_live_event_delete_selected")
     *
     * @return JsonResponse
     * @return JsonResponse
     */
    public function deleteSelectedEventsAction(Request $request)
    {
        $data = $request->request->get('events_checkbox');
        foreach ($data as $multimediaObjectId) {
            $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(
                ['_id' => new ObjectId($multimediaObjectId)]
            );
            $this->deleteEvent($multimediaObject);
        }

        return new JsonResponse([]);
    }

    /**
     * Edit action, opens well with event data.
     *
     * @return array
     * @Route("edit/{id}", name="pumukit_new_admin_live_event_edit")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id":
     *                                     "id"}})
     * @Template("PumukitNewAdminBundle:LiveEvent:edit.html.twig")
     */
    public function editEventAction(MultimediaObject $multimediaObject)
    {
        $this->container->get('session')->set('admin/live/event/id', $multimediaObject->getId());

        return ['multimediaObject' => $multimediaObject];
    }

    /**
     * Form session to create or edit.
     *
     * @Route("event/{id}", name="pumukit_new_admin_live_event_eventtab")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id":
     *                                     "id"}})
     * @Template("PumukitNewAdminBundle:LiveEvent:updateevent.html.twig")
     *
     * @throws \Exception
     *
     * @return array|jsonResponse
     */
    public function eventAction(Request $request, MultimediaObject $multimediaObject)
    {
        $locale = $request->getLocale();

        $form = $this->createForm(EventsType::class, $multimediaObject->getEmbeddedEvent(), ['translator' => $this->translatorService, 'locale' => $locale]);

        $people = [];
        $people['author'] = $multimediaObject->getEmbeddedEvent()->getAuthor();
        $people['producer'] = $multimediaObject->getEmbeddedEvent()->getProducer();

        $enableChat = $this->getParameter('pumukit_live.chat.enable');
        $enableTwitter = $this->getParameter('pumukit_live.twitter.enable');
        $enableContactForm = $this->getParameter('liveevent_contact_and_share');
        $twitterAccountsLinkColor = $this->getParameter('pumukit_live.twitter.accounts_link_color');

        $autocompleteSeries = $this->getParameter('pumukit_new_admin.advance_live_event_autocomplete_series');

        $form->handleRequest($request);
        if ('POST' === $request->getMethod()) {
            try {
                $data = $request->request->get('pumukitnewadmin_live_event');

                $event = $multimediaObject->getEmbeddedEvent();

                foreach ($data['i18n_name'] as $language => $value) {
                    $event->setName($value, $language);
                }
                foreach ($data['i18n_description'] as $language => $value) {
                    $event->setDescription($value, $language);
                }

                $event->setPlace($data['place']);
                if (isset($data['date'])) {
                    $event->setDate(new \DateTime($data['date']));
                }
                $event->setDuration($data['duration']);
                $display = isset($data['display']) ? true : false;
                $event->setDisplay($display);
                $externalURL = $data['externalURL'] ?? '';
                $event->setUrl($externalURL);

                if (isset($data['live'])) {
                    $live = $this->documentManager->getRepository(Live::class)->findOneBy(
                        ['_id' => new ObjectId($data['live'])]
                    );
                    $event->setLive($live);
                }
                if ($enableContactForm && isset($data['contact'])) {
                    if ($multimediaObject->getEmbeddedSocial()) {
                        $multimediaObject->getEmbeddedSocial()->setEmail($data['contact']);
                    } else {
                        $embeddedSocial = new EmbeddedSocial();
                        $embeddedSocial->setEmail($data['contact']);
                        $this->documentManager->persist($embeddedSocial);
                        $multimediaObject->setEmbeddedSocial($embeddedSocial);
                    }
                } else {
                    if ($multimediaObject->getEmbeddedSocial()) {
                        $multimediaObject->getEmbeddedSocial()->setEmail('');
                    }
                }

                if (isset($data['author'])) {
                    $multimediaObject->getEmbeddedEvent()->setAuthor($data['author']);
                }

                if (isset($data['producer'])) {
                    $multimediaObject->getEmbeddedEvent()->setProducer($data['producer']);
                }

                if ($enableTwitter && isset($data['twitter_hashtag'])) {
                    if ($multimediaObject->getEmbeddedSocial()) {
                        $multimediaObject->getEmbeddedSocial()->setTwitterHashtag($data['twitter_hashtag']);
                    } else {
                        $embeddedSocial = new EmbeddedSocial();
                        $embeddedSocial->setTwitterHashtag($data['twitter_hashtag']);
                        $this->documentManager->persist($embeddedSocial);
                        $multimediaObject->setEmbeddedSocial($embeddedSocial);
                    }
                }
                if ($enableTwitter && isset($data['twitter_widget_id'])) {
                    if ($multimediaObject->getEmbeddedSocial()) {
                        $multimediaObject->getEmbeddedSocial()->setTwitter($data['twitter_widget_id']);
                    } else {
                        $embeddedSocial = new EmbeddedSocial();
                        $embeddedSocial->setTwitter($data['twitter_widget_id']);
                        $this->documentManager->persist($embeddedSocial);
                        $multimediaObject->setEmbeddedSocial($embeddedSocial);
                    }
                }

                $color = $this->eventsService->validateHtmlColor($data['poster_text_color']);
                $multimediaObject->setProperty('postertextcolor', $color);

                $this->documentManager->flush();
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], 409);
            }

            return new JsonResponse(['event' => $multimediaObject->getEmbeddedEvent()]);
        }

        return ['form' => $form->createView(), 'multimediaObject' => $multimediaObject, 'people' => $people, 'enableChat' => $enableChat, 'enableTwitter' => $enableTwitter, 'twitterAccountsLinkColor' => $twitterAccountsLinkColor, 'enableContactForm' => $enableContactForm, 'autocomplete_series' => $autocompleteSeries];
    }

    /**
     * @Route("series/{id}", name="pumukit_new_admin_live_event_seriestab")
     * @ParamConverter("series", class="PumukitSchemaBundle:Series", options={"mapping": {"id": "id"}})
     * @Template("PumukitNewAdminBundle:Series:updatemeta.html.twig")
     *
     * @return array
     */
    public function seriesAction(Request $request, Series $series)
    {
        $locale = $request->getLocale();
        $disablePudenew = !$this->getParameter('show_latest_with_pudenew');

        $form = $this->createForm(SeriesType::class, $series, ['translator' => $this->translatorService, 'locale' => $locale, 'disable_PUDENEW' => $disablePudenew]);

        $exclude_fields = [];
        $show_later_fields = [
            'pumukitnewadmin_series_i18n_header',
            'pumukitnewadmin_series_i18n_footer',
            'pumukitnewadmin_series_i18n_line2',
            'pumukitnewadmin_series_template',
        ];
        $showSeriesTypeTab = $this->hasParameter(
            'pumukit.use_series_channels'
        ) && $this->getParameter('pumukit.use_series_channels');
        if (!$showSeriesTypeTab) {
            $exclude_fields[] = 'pumukitnewadmin_series_series_type';
        }

        return [
            'form' => $form->createView(),
            'series' => $series,
            'exclude_fields' => $exclude_fields,
            'show_later_fields' => $show_later_fields,
        ];
    }

    /**
     * Form session to create or edit.
     *
     * @Route("session/{id}", name="pumukit_new_admin_live_event_sessiontab")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id":
     *                                     "id"}})
     * @Template("PumukitNewAdminBundle:LiveEvent:updatesession.html.twig")
     *
     * @return array|jsonResponse
     */
    public function sessionAction(Request $request, MultimediaObject $multimediaObject)
    {
        $locale = $request->getLocale();

        $form = $this->createForm(EmbeddedEventSessionType::class, null, ['translator' => $this->translatorService, 'locale' => $locale]);

        $form->handleRequest($request);
        if ('POST' === $request->getMethod()) {
            try {
                $data = $form->getData();
                $start = new \DateTime($data->getStart());
                $end = new \DateTime($data->getDuration());
                $duration = $end->getTimestamp() - $start->getTimestamp();
                $notes = $data->getNotes();

                $data = $request->request->get('pumukitnewadmin_event_session');
                if (isset($data['id'])) {
                    foreach ($multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession(
                    ) as $embeddedEventSession) {
                        if ($embeddedEventSession->getId() == $data['id']) {
                            $embeddedEventSession->setStart($start);
                            $embeddedEventSession->setEnds($end);
                            $embeddedEventSession->setDuration($duration);
                            $embeddedEventSession->setNotes($notes);
                        }
                    }
                } else {
                    $embeddedEventSession = new EmbeddedEventSession();

                    $embeddedEventSession->setStart($start);
                    $embeddedEventSession->setEnds($end);
                    $embeddedEventSession->setDuration($duration);
                    $embeddedEventSession->setNotes($notes);
                    $this->documentManager->persist($embeddedEventSession);

                    $multimediaObject->getEmbeddedEvent()->addEmbeddedEventSession($embeddedEventSession);
                }

                $this->documentManager->flush();
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], 409);
            }

            return new JsonResponse(
                ['sessions' => $multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession()]
            );
        }

        return ['multimediaObject' => $multimediaObject, 'form' => $form->createView()];
    }

    /**
     * @Route("list/session/{id}", name="pumukit_new_admin_live_event_session_list")
     * @Template("PumukitNewAdminBundle:LiveEvent:sessionlist.html.twig")
     *
     * @param string $id
     *
     * @return array
     */
    public function sessionListAction($id)
    {
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($id)]);

        return ['multimediaObject' => $multimediaObject];
    }

    /**
     * @Route("delete/session/{multimediaObject}/{session_id}", name="pumukit_new_admin_live_event_session_delete")
     * @Template("PumukitNewAdminBundle:LiveEvent:sessionlist.html.twig")
     *
     * @param string $multimediaObject
     * @param string $session_id
     *
     * @return JsonResponse
     */
    public function sessionDeleteAction($multimediaObject, $session_id)
    {
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($multimediaObject)]);
        foreach ($multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession() as $session) {
            if ($session->getId() == $session_id) {
                $multimediaObject->getEmbeddedEvent()->removeEmbeddedEventSession($session);
            }
        }

        $this->documentManager->flush();

        return new JsonResponse(['sessions' => $multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession()]);
    }

    /**
     * @Route("clone/session/{multimediaObject}/{session_id}", name="pumukit_new_admin_live_event_clone_session")
     * @Template("PumukitNewAdminBundle:LiveEvent:sessionlist.html.twig")
     *
     * @param string $multimediaObject
     * @param string $session_id
     *
     * @throws \Exception
     *
     * @return JsonResponse
     */
    public function sessionCloneAction($multimediaObject, $session_id)
    {
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($multimediaObject)]);
        foreach ($multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession() as $session) {
            if ($session->getId() == $session_id) {
                $newSession = new EmbeddedEventSession();
                $newSession->setDuration($session->getDuration());
                $newSession->setNotes($session->getNotes());
                $date = clone $session->getStart();
                $dateEnds = clone $session->getEnds();
                $date->add(new \DateInterval('P1D'));
                $dateEnds->add(new \DateInterval('P1D'));
                $newSession->setStart($date);
                $newSession->setEnds($dateEnds);
                $this->documentManager->persist($newSession);
                $multimediaObject->getEmbeddedEvent()->addEmbeddedEventSession($newSession);
            }
        }

        $this->documentManager->flush();

        return new JsonResponse(['sessions' => $multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession()]);
    }

    /**
     * @Route("modal/{multimediaObject}/{session_id}", name="pumukit_new_admin_live_event_session_modal")
     * @Template("PumukitNewAdminBundle:LiveEvent:updatesessionmodal.html.twig")
     *
     * @param string $multimediaObject
     * @param bool   $session_id
     *
     * @throws \Exception
     *
     * @return array
     */
    public function modalSessionAction(Request $request, $multimediaObject, $session_id = false)
    {
        $locale = $request->getLocale();

        $form = $this->createForm(EmbeddedEventSessionType::class, null, ['translator' => $this->translatorService, 'locale' => $locale]);

        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($multimediaObject)]);

        if (!$session_id) {
            return ['form' => $form->createView(), 'multimediaObject' => $multimediaObject];
        }

        $sessionData = '';
        foreach ($multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession() as $session) {
            if ($session->getId() == $session_id) {
                $sessionData = $session;
            }
        }

        if (!empty($sessionData)) {
            $start = $sessionData->getStart();
            $form->get('start')->setData($start);
            $duration = clone $sessionData->getStart();
            $duration->add(new \DateInterval('PT'.$sessionData->getDuration().'S'));
            $form->get('duration')->setData($duration);
            $form->get('notes')->setData($sessionData->getNotes());
        }

        return [
            'form' => $form->createView(),
            'multimediaObject' => $multimediaObject,
            'session_id' => $session_id,
        ];
    }

    /**
     * @return JsonResponse
     * @Route("series/suggest/", name="pumukit_new_admin_live_event_series_suggest")
     */
    public function seriesSuggestAction(Request $request)
    {
        $value = $request->query->get('term');

        $aggregate = $this->documentManager->getDocumentCollection(Series::class);

        $user = $this->getUser();
        $pipeline = [];
        $pipeline[] = ['$match' => ['title.'.$request->getLocale() => new Regex($value, 'i')]];
        $pipeline[] = ['$match' => ['type' => Series::TYPE_SERIES]];

        if ($user->hasRole(PermissionProfile::SCOPE_PERSONAL)) {
            $pipeline[] = ['$match' => ['properties.owners' => $user->getId()]];
        }

        $pipeline[] = [
            '$group' => [
                '_id' => [
                    'id' => '$_id',
                    'title' => '$title',
                ],
            ],
        ];

        $pipeline[] = ['$limit' => 100];

        $series = $aggregate->aggregate($pipeline, ['cursor' => []])->toArray();

        $result = [];
        foreach ($series as $key => $dataSeries) {
            $result[] = [
                'id' => (string) $dataSeries['_id']['id'],
                'title' => $dataSeries['_id']['title'][$request->getLocale()],
                'label' => $dataSeries['_id']['title'][$request->getLocale()],
                'value' => $dataSeries['_id']['id'].' - '.$dataSeries['_id']['title'][$request->getLocale()],
            ];
        }

        return new JsonResponse($result);
    }

    /**
     * @Route("change/series/{multimediaObject}", name="pumukitnewadmin_live_event_change_series")
     * @Template("PumukitNewAdminBundle:LiveEvent:changeSeries.html.twig")
     *
     * @param mixed|null $multimediaObject
     *
     * @return array
     */
    public function seriesChangeModalAction($multimediaObject = null)
    {
        if (isset($multimediaObject)) {
            $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($multimediaObject)]);

            return ['multimediaObject' => $multimediaObject];
        }

        return [];
    }

    /**
     * @return JsonResponse
     *
     * @Route("edit/series/{multimediaObject}", name="pumukitnewadmin_live_event_edit_series")
     */
    public function seriesChangeAction(Request $request, MultimediaObject $multimediaObject)
    {
        $series = $request->request->get('seriesSuggest');
        if ($series) {
            $series = $this->documentManager->getRepository(Series::class)->findOneBy(['_id' => new ObjectId($series)]);
            if ($series) {
                $multimediaObject->setSeries($series);
                $this->documentManager->flush();

                return new JsonResponse(['success']);
            }

            return new JsonResponse(['error']);
        }

        return new JsonResponse(['error']);
    }

    /**
     * @return array
     * @Route("show/{id}", name="pumukit_new_admin_live_event_show")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id":
     *                                     "id"}})
     * @Template("PumukitNewAdminBundle:LiveEvent:show.html.twig")
     */
    public function showAction(MultimediaObject $multimediaObject)
    {
        return ['multimediaObject' => $multimediaObject];
    }

    /**
     * @Route("autocomplete/series/with/event/data/{id}", name="pumukit_new_admin_autocomplete_series_with_event_data")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id": "id"}})
     *
     * @throws \Exception
     *
     * @return JsonResponse
     */
    public function autocompleteSeriesWithEventDataAction(Request $request, MultimediaObject $multimediaObject)
    {
        $series = $this->documentManager->getRepository(Series::class)->findOneBy(['_id' => $multimediaObject->getSeries()->getId()]);
        if (!$series) {
            throw new \Exception($this->translatorService->trans('Series not found'));
        }

        try {
            $series->setI18nTitle($multimediaObject->getEmbeddedEvent()->getI18nName());
            $series->setI18nDescription($multimediaObject->getEmbeddedEvent()->getI18nDescription());
            $this->documentManager->flush();
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }

        $this->seriesDispatcher->dispatchUpdate($series);

        return new JsonResponse(['success']);
    }

    /**
     * clone Event and series.
     *
     * @throws \Exception
     *
     * @return string
     */
    private function cloneEvent(MultimediaObject $multimediaObject)
    {
        $cloneMultimediaObject = $this->factoryService->cloneMultimediaObject($multimediaObject);
        $cloneMultimediaObject->setType(MultimediaObject::TYPE_LIVE);

        $this->documentManager->persist($cloneMultimediaObject);

        $series = $multimediaObject->getSeries();
        $cloneSeries = clone $series;
        $this->documentManager->persist($cloneSeries);

        $cloneMultimediaObject->setSeries($cloneSeries);

        $event = new EmbeddedEvent();
        $event->setDate(new \DateTime());
        $event->setI18nName($multimediaObject->getEmbeddedEvent()->getI18nName());
        $event->setI18nDescription($multimediaObject->getEmbeddedEvent()->getI18nDescription());
        $event->setPlace($multimediaObject->getEmbeddedEvent()->getPlace());
        $event->setDuration($multimediaObject->getEmbeddedEvent()->getDuration());
        $event->setDisplay($multimediaObject->getEmbeddedEvent()->isDisplay());
        $event->setLive($multimediaObject->getEmbeddedEvent()->getLive());
        $event->setUrl($multimediaObject->getEmbeddedEvent()->getUrl());
        $event->setCreateSerial(false);
        $this->documentManager->persist($event);

        $cloneMultimediaObject->setEmbeddedEvent($event);
        $this->documentManager->persist($cloneMultimediaObject);
        $this->documentManager->flush();

        return 'Cloned event successfully';
    }

    /**
     * Delete event and multimediaObject.
     *
     * @return string
     */
    private function deleteEvent(MultimediaObject $multimediaObject)
    {
        $this->factoryService->deleteMultimediaObject($multimediaObject);

        return 'Deleted event successfully';
    }

    /**
     * Delete event, multimediaObject and series if serie have just one multimediaObject.
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     *
     * @return string
     */
    private function deleteEventAndSeries(MultimediaObject $multimediaObject)
    {
        $aggregate = $this->documentManager->getDocumentCollection(MultimediaObject::class);
        $user = $this->getUser();
        $pipeline = [];
        $pipeline[] = ['$match' => ['series' => new ObjectId($multimediaObject->getSeries()->getId())]];
        $ownerKey = $this->getParameter('pumukitschema.personal_scope_role_code');
        if ($user->hasRole(PermissionProfile::SCOPE_PERSONAL)) {
            $pipeline[] = ['$match' => ['people.people.email' => ['$ne' => $user->getEmail()]]];
            $pipeline[] = ['$match' => ['people.cod' => $ownerKey]];
        }
        $pipeline[] = [
            '$group' => [
                '_id' => ['id' => '$_id'],
            ],
        ];
        $mmObjsNotOwner = $aggregate->aggregate($pipeline, ['cursor' => []])->toArray();

        if (0 !== count($mmObjsNotOwner) && $user->hasRole(PermissionProfile::SCOPE_PERSONAL)) {
            throw new \Exception($this->translatorService->trans('Error: Series have another owners on others events'));
        }
        $series = $multimediaObject->getSeries();
        $seriesRepo = $this->documentManager->getRepository(Series::class);
        $count = $seriesRepo->countMultimediaObjects($series);
        if (1 === $count) {
            $this->factoryService->deleteMultimediaObject($multimediaObject);
            $this->factoryService->deleteSeries($series);
        } else {
            throw new \Exception($this->translatorService->trans('Error: Series have some events'));
        }

        return 'Delete event and series successfully';
    }

    private function reorderMultimediaObjectsByNextNearSession($multimediaObjects, $sortType)
    {
        $date = new \DateTime();

        usort($multimediaObjects, function ($a, $b) use ($sortType, $date) {
            $validSessionA = null;
            foreach ($a->getEmbeddedEvent()->getEmbeddedEventSession() as $sessionA) {
                if ($sessionA->getStart() > $date || ($sessionA->getStart() <= $date && $sessionA->getEnds() > $date)) {
                    $validSessionA = $sessionA->getStart()->getTimestamp();

                    break;
                }
            }
            $validSessionB = null;
            foreach ($b->getEmbeddedEvent()->getEmbeddedEventSession() as $sessionB) {
                if ($sessionB->getStart() > $date || ($sessionB->getStart() <= $date && $sessionB->getEnds() > $date)) {
                    $validSessionB = $sessionB->getStart()->getTimestamp();

                    break;
                }
            }

            if (!$validSessionA && !$validSessionB) {
                return 0;
            }

            if (!$validSessionA && $validSessionB) {
                return 1;
            }

            if ($validSessionA && !$validSessionB) {
                return -1;
            }

            if ($validSessionA && $validSessionB) {
                if ('desc' == $sortType) {
                    return ($validSessionA < $validSessionB) ? 1 : -1;
                }

                return ($validSessionA < $validSessionB) ? -1 : 1;
            }
        });

        return $multimediaObjects;
    }
}
