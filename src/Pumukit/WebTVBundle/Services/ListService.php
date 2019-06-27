<?php

namespace Pumukit\WebTVBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
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
        $publishingDecisionTag = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $this->publishingDecisionCode]);
        $tag = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $this->wallTag]);
        if (!$tag || !$tag->isDescendantOf($publishingDecisionTag)) {
            throw new \Exception('Configured tag for wall block ('.$this->wallTag.') doesnt exists or is not child of '.$this->publishingDecisionCode);
        }

        $criteria = [
            'tags.cod' => $this->wallTag,
        ];

        $objects = $this->documentManager->getRepository(MultimediaObject::class)->findStandardBy($criteria);

        return $objects;
    }
}
