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
    private $wallTag;

    private $publishingDecisionCode = 'PUBDECISIONS';

    /**
     * ListService constructor.
     *
     * @param DocumentManager             $documentManager
     * @param EmbeddedEventSessionService $embeddedEventSessionService
     * @param string                      $advanceLiveEvents
     * @param string                      $wallTag
     */
    public function __construct(DocumentManager $documentManager, EmbeddedEventSessionService $embeddedEventSessionService, $advanceLiveEvents, $wallTag)
    {
        $this->documentManager = $documentManager;
        $this->embeddedEventSessionService = $embeddedEventSessionService;
        $this->advanceLiveEvents = $advanceLiveEvents;
        $this->wallTag = $wallTag;
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

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function getWallVideos()
    {
        $criteria = [
            'tags.cod' => $this->wallTag,
        ];

        $objects = $this->documentManager->getRepository(MultimediaObject::class)->findStandardBy($criteria);

        return $objects;
    }
}
