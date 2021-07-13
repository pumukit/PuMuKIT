<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Track;

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
        return $this->defaultHeadVideo ?: null;
    }

    public function getSystemDefaultTail(): ?string
    {
        return $this->defaultTailVideo ?: null;
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
        if ($multimediaObject->getVideoHead()) {
            return $multimediaObject->getVideoHead();
        }

        $series = $this->documentManager->getRepository(Series::class)->findOneBy([
            '_id' => new ObjectId($multimediaObject->getSeries()->getId()),
        ]);

        if ($series instanceof Series && $series->getVideoHead()) {
            return $series->getVideoHead();
        }

        return $this->getSystemDefaultHeader();
    }

    public function getDisplayTrackFromMultimediaObjectId(string $multimediaObjectId): ?Track
    {
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy([
            '_id' => new ObjectId($multimediaObjectId),
        ]);

        if (!$multimediaObject instanceof MultimediaObject) {
            throw new \Exception('Multimedia Object not found');
        }

        return $multimediaObject->getDisplayTrack();
    }

    public function getTailToPlay(MultimediaObject $multimediaObject): ?string
    {
        if ($multimediaObject->getVideoTail()) {
            return $multimediaObject->getVideoTail();
        }

        $series = $this->documentManager->getRepository(Series::class)->findOneBy([
            '_id' => new ObjectId($multimediaObject->getSeries()->getId()),
        ]);

        if ($series instanceof Series && $series->getVideoTail()) {
            return $series->getVideoTail();
        }

        return $this->getSystemDefaultTail();
    }

    public function removeElement(string $type, string $element): bool
    {
        if (!in_array(strtolower($type), ['head', 'tail'])) {
            return false;
        }

        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(
            [
                '_id' => new ObjectId($element), ]
        );

        if (!$multimediaObject instanceof MultimediaObject) {
            return false;
        }

        if ('head' === strtolower($type)) {
            $tag = $this->getTagWithCode($this->getHeadTagCode());
            $multimediaObject->removeTag($tag);
            $this->documentManager->flush();

            $this->removeHeadElementOnAllMultimediaObjectsAndSeries($element);

            return true;
        }

        if ('tail' === strtolower($type)) {
            $tag = $this->getTagWithCode($this->getTailTagCode());
            $multimediaObject->removeTag($tag);
            $this->documentManager->flush();

            $this->removeTailElementOnAllMultimediaObjectsAndSeries($element);

            return true;
        }

        return false;
    }

    private function removeHeadElementOnAllMultimediaObjectsAndSeries(string $element)
    {
        $this->removeElementOnMultimediaObjectAndSeries('head', $element);
    }

    private function removeTailElementOnAllMultimediaObjectsAndSeries(string $element)
    {
        $this->removeElementOnMultimediaObjectAndSeries('tail', $element);
    }

    private function removeElementOnMultimediaObjectAndSeries(string $type, string $element)
    {
        $criteria = ['videoTail' => $element];
        $method = 'setVideoTail';
        if ('head' === strtolower($type)) {
            $criteria = ['videoHead' => $element];
            $method = 'setVideoHead';
        }

        $multimediaObjects = $this->documentManager->getRepository(MultimediaObject::class)->findBy($criteria);

        foreach ($multimediaObjects as $multimediaObject) {
            $multimediaObject->{$method}(null);
        }

        $series = $this->documentManager->getRepository(Series::class)->findBy($criteria);
        foreach ($series as $oneSeries) {
            $oneSeries->{$method}(null);
        }

        $this->documentManager->flush();
    }

    private function getTagWithCode(string $code): Tag
    {
        $tag = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $code]);
        if ($tag instanceof Tag) {
            return $tag;
        }

        throw new \Exception("Tag with code ${code} not found");
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
