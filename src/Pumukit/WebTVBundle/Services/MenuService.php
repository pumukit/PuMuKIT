<?php

namespace Pumukit\WebTVBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Event;
use Pumukit\SchemaBundle\Document\Live;
use Pumukit\SchemaBundle\Services\EmbeddedEventSessionService;

/**
 * Class MenuService.
 */
class MenuService
{
    /**
     * @var DocumentManager
     */
    private $dm;
    /**
     * @var EmbeddedEventSessionService
     */
    private $eventSessionService;
    private $advanceEvents;

    /**
     * MenuService constructor.
     *
     * @param DocumentManager             $documentManager
     * @param EmbeddedEventSessionService $eventSessionService
     * @param Event[]                     $advanceEvents
     */
    public function __construct(DocumentManager $documentManager, EmbeddedEventSessionService $eventSessionService, $advanceEvents)
    {
        $this->dm = $documentManager;
        $this->eventSessionService = $eventSessionService;
        $this->advanceEvents = $advanceEvents;
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    public function getMenuEventsElement()
    {
        if (!$this->advanceEvents) {
            [$events, $channels, $liveEventTypeSession] = $this->getEvents();
        } else {
            [$events, $channels, $liveEventTypeSession] = $this->getAdvanceEvents();
        }

        return [$events, $channels, $liveEventTypeSession];
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    private function getAdvanceEvents()
    {
        $events = $this->eventSessionService->findEventsMenu();
        $menuEvents = [];
        $nowOrFuture = false;
        foreach ($events as $event) {
            foreach ($event['data'] as $sessionData) {
                $sec = $sessionData['session']['ends']->sec;
                $date = new \DateTime();
                $ends = $date->setTimestamp($sec);
                if (new \DateTime() < $ends) {
                    $nowOrFuture = true;
                }
                $sessionStart = $sessionData['session']['start']->sec;
                $todayEnds = strtotime(date('Y-m-d H:i:s', mktime(23, 59, 59, date('m'), date('d'), date('Y'))));
                if ($sessionStart > $todayEnds) {
                    $nowOrFuture = false;
                }
                if ($nowOrFuture) {
                    $menuEvents[(string) $event['_id']] = [];
                    $menuEvents[(string) $event['_id']]['sort'] = $sessionStart;
                    $menuEvents[(string) $event['_id']]['event'] = $sessionData['event'];
                    $menuEvents[(string) $event['_id']]['sessions'][] = $sessionData['session'];
                    $nowOrFuture = false;
                }
            }
        }
        uasort(
            $menuEvents,
            function ($a, $b) {
                if ($a['sort'] == $b['sort']) {
                    return 0;
                }

                return ($a < $b) ? -1 : 1;
            }
        );

        return [
            $menuEvents,
            [],
            true,
        ];
    }

    /**
     * @return array
     */
    private function getEvents()
    {
        $channels = $this->dm->getRepository(Live::class)->findAll();
        $events = $this->dm->getRepository(Event::class)->findNextEvents();
        $liveEventTypeSession = false;

        return [
            $channels,
            $events,
            $liveEventTypeSession,
        ];
    }
}
