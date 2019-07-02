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
     * @param $limit
     *
     * @return array
     */
    public function getLives($limit)
    {
        if (!$this->advanceLiveEvents) {
            return [];
        }

        $objects = $this->embeddedEventSessionService->findCurrentSessions([], $limit);

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
