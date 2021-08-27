<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
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
        return $multimediaObject->isHead();
    }

    public function isTail(MultimediaObject $multimediaObject): bool
    {
        return $multimediaObject->isTail();
    }

    public function getVideosAsHead(): array
    {
        return $this->getHeadElements();
    }

    public function getVideosAsTail(): array
    {
        return $this->getTailElements();
    }

    public function getHead(): array
    {
        $elements = $this->getHeadElements();

        return $this->processReturnData($elements);
    }

    public function getTail(): array
    {
        $elements = $this->getTailElements();

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
        if ($this->documentManager->getFilterCollection()->isEnabled('frontend')) {
            $this->documentManager->getFilterCollection()->disable('frontend');
        }

        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy([
            '_id' => new ObjectId($multimediaObjectId),
            'status' => ['$ne' => [MultimediaObject::STATUS_PROTOTYPE, MultimediaObject::STATUS_NEW]],
        ]);

        $this->documentManager->getFilterCollection()->enable('frontend');

        if (!$multimediaObject instanceof MultimediaObject) {
            return null;
        }

        if ($multimediaObject->isPublished() && $multimediaObject->containsTagWithCod('PUCHWEBTV')) {
            return $multimediaObject->getDisplayTrack();
        }

        return null;
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
            $multimediaObject->setHead(false);
            $this->documentManager->flush();

            $this->removeHeadElementOnAllMultimediaObjectsAndSeries($element);

            return true;
        }

        if ('tail' === strtolower($type)) {
            $multimediaObject->setTags(false);
            $this->documentManager->flush();

            $this->removeTailElementOnAllMultimediaObjectsAndSeries($element);

            return true;
        }

        return false;
    }

    public function removeHeadElementOnAllMultimediaObjectsAndSeries(string $element)
    {
        $this->removeElementOnMultimediaObjectAndSeries('head', $element);
    }

    public function removeTailElementOnAllMultimediaObjectsAndSeries(string $element)
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

    private function getHeadElements(): array
    {
        return $this->documentManager->getRepository(MultimediaObject::class)->findBy([
            'head' => true,
        ]);
    }

    private function getTailElements(): array
    {
        return $this->documentManager->getRepository(MultimediaObject::class)->findBy([
            'tail' => true,
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
}
