<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class HeadAndTailService
{
    private $documentManager;
    private $defaultHeadVideo;
    private $defaultTailVideo;

    public function __construct(DocumentManager $documentManager, ?string $defaultHeadVideo, ?string $defaultTailVideo)
    {
        $this->documentManager = $documentManager;
        $this->defaultHeadVideo = $defaultHeadVideo;
        $this->defaultTailVideo = $defaultTailVideo;
    }

    public function getSystemDefaultHeader(): ?string
    {
        return $this->defaultHeadVideo ?: 'Default video head not configured';
    }

    public function getSystemDefaultTail(): ?string
    {
        return $this->defaultTailVideo ?: 'Default video tail not configured';
    }

    public function isHead(MultimediaObject $multimediaObject): bool
    {
        return $multimediaObject->containsTagWithCod($this->getHeadTagCode());
    }

    public function isTail(MultimediaObject $multimediaObject): bool
    {
        return $multimediaObject->containsTagWithCod($this->getTailTagCode());
    }

    public function getVideosAsHead(): array
    {
        return $this->getElementsByTagCode($this->getHeadTagCode());
    }

    public function getVideosAsTail(): array
    {
        return $this->getElementsByTagCode($this->getTailTagCode());
    }

    public function getHead(): array
    {
        $elements = $this->getElementsByTagCode($this->getHeadTagCode());

        return $this->processReturnData($elements);
    }

    public function getTail(): array
    {
        $elements = $this->getElementsByTagCode($this->getTailTagCode());

        return $this->processReturnData($elements);
    }

    public function getHeadToPlay(MultimediaObject $multimediaObject)
    {
        if($multimediaObject->getVideoHead()) {
            return $multimediaObject->getVideoHead();
        }

        $series = $this->documentManager->getRepository(Series::class)->findOneBy([
            '_id' => new ObjectId($multimediaObject->getSeries()->getId())
        ]);

        if($series instanceof Series && $series->getVideoHead()) {
            return $series->getVideoHead();
        }

        return $this->getSystemDefaultHeader();
    }

    public function getTailToPlay(MultimediaObject $multimediaObject): ?string
    {
        if($multimediaObject->getVideoTail()) {
            return $multimediaObject->getVideoTail();
        }

        $series = $this->documentManager->getRepository(Series::class)->findOneBy([
            '_id' => new ObjectId($multimediaObject->getSeries()->getId())
        ]);

        if($series instanceof Series && $series->getVideoTail()) {
            return $series->getVideoTail();
        }

        return $this->getSystemDefaultTail();
    }

    private function getElementsByTagCode(string $tagCode): array
    {
        return $this->documentManager->getRepository(MultimediaObject::class)->findBy([
            'tags.cod' => $tagCode,
        ]);
    }

    private function processReturnData($elements): array
    {
        $resultData = [];
        foreach ($elements as $element) {
            $resultData[$element->getId()] = $element->getTitle();
        }

        return $resultData;
    }

    private function getHeadTagCode(): string
    {
        return 'PUDEVIDEOHEAD';
    }

    private function getTailTagCode(): string
    {
        return 'PUDEVIDEOTAIL';
    }
}
