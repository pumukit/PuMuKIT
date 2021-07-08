<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;

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

    private function getElementsByTagCode(string $tagCode): array
    {
        return $this->documentManager->getRepository(MultimediaObject::class)->findBy([
            'tags.cod' => $tagCode
        ]);
    }

    private function processReturnData($elements): array
    {
        $resultData = [];
        foreach($elements as $element) {
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
