<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\TextIndexService;

class MultimediaObjectListener
{
    private DocumentManager $dm;
    private TextIndexService $textIndexService;
    private LoggerInterface $logger;

    public function __construct(DocumentManager $dm, TextIndexService $textIndexService, LoggerInterface $logger)
    {
        $this->dm = $dm;
        $this->textIndexService = $textIndexService;
        $this->logger = $logger;
    }

    public function postUpdate($event): void
    {
        $multimediaObject = $event->getMultimediaObject();
        $this->updateTextIndex($multimediaObject);
        $this->dm->flush();
    }

    public function updateTextIndex(MultimediaObject $multimediaObject): void
    {
        $this->textIndexService->updateMultimediaObjectTextIndex($multimediaObject);
    }

    private function getTracksType($tracks): int
    {
        if (0 === (is_countable($tracks) ? count($tracks) : 0)) {
            return MultimediaObject::TYPE_UNKNOWN;
        }

        foreach ($tracks as $track) {
            if (!$track->metadata()->isOnlyAudio()) {
                return MultimediaObject::TYPE_VIDEO;
            }
        }

        return MultimediaObject::TYPE_AUDIO;
    }
}
