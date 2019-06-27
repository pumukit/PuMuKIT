<?php

namespace Pumukit\WebTVBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\EmbeddedEventSessionService;

class ListService
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var EmbeddedEventSessionService
     */
    private $embeddedEventSessionService;

    private $advanceLiveEvents;

    /**
     * ListService constructor.
     *
     * @param DocumentManager             $documentManager
     * @param EmbeddedEventSessionService $embeddedEventSessionService
     * @param                             $advanceLiveEvents
     */
    public function __construct(DocumentManager $documentManager, EmbeddedEventSessionService $embeddedEventSessionService, $advanceLiveEvents)
    {
        $this->documentManager = $documentManager;
        $this->embeddedEventSessionService = $embeddedEventSessionService;
        $this->advanceLiveEvents = $advanceLiveEvents;
    }

    /**
     * @return array
     */
    public function getLives()
    {
        if (!$this->advanceLiveEvents) {
            return [];
        }

        $objects = $this->embeddedEventSessionService->findCurrentSessions();

        return $objects;
    }
}
