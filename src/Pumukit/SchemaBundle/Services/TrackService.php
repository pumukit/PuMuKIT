<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\SchemaBundle\Document\MediaType\Document;
use Pumukit\SchemaBundle\Document\MediaType\Image;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Finder\Finder;

class TrackService
{
    private DocumentManager $dm;

    private TrackEventDispatcherService $dispatcher;
    private ?string $tmpPath;
    private bool $forceDeleteOnDisk;
    private LoggerInterface $logger;

    public function __construct(
        DocumentManager $documentManager,
        TrackEventDispatcherService $dispatcher,
        LoggerInterface $logger,
        ?string $tmpPath = null,
        bool $forceDeleteOnDisk = true
    ) {
        $this->dm = $documentManager;
        $this->dispatcher = $dispatcher;
        $this->tmpPath = $tmpPath ? realpath($tmpPath) : sys_get_temp_dir();
        $this->forceDeleteOnDisk = $forceDeleteOnDisk;
        $this->logger = $logger;
    }

    public function addTrackToMultimediaObject(MultimediaObject $multimediaObject, MediaInterface $media, bool $executeFlush = true): MultimediaObject
    {
        if ($media instanceof Track) {
            $multimediaObject->addTrack($media);
        }

        if ($media instanceof Image) {
            $multimediaObject->addImage($media);
        }

        if ($media instanceof Document) {
            $multimediaObject->addDocument($media);
        }

        if ($executeFlush) {
            $this->dm->persist($multimediaObject);
            $this->dm->flush();
        }

        $this->dispatcher->dispatchCreate($multimediaObject, $media);

        return $multimediaObject;
    }

    public function updateTrackInMultimediaObject(MultimediaObject $multimediaObject, Track $track): MultimediaObject
    {
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->dispatcher->dispatchUpdate($multimediaObject, $track);

        return $multimediaObject;
    }

    public function removeTrackFromMultimediaObject(MultimediaObject $multimediaObject, string $trackId): MultimediaObject
    {
        $track = $multimediaObject->getTrackById($trackId);
        $trackPath = $track->getPath();

        $isNotOpencast = !$track->containsTag('opencast');

        $multimediaObject->removeTrackById($trackId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        if ($this->forceDeleteOnDisk && $trackPath && $isNotOpencast) {
            $countOtherTracks = $this->countMultimediaObjectWithTrack($trackPath);

            if (0 === $countOtherTracks) {
                $this->deleteFileOnDisk($trackPath);
            }
        }

        $this->dispatcher->dispatchDelete($multimediaObject, $track);

        return $multimediaObject;
    }

    public function upTrackInMultimediaObject(MultimediaObject $multimediaObject, string $trackId): MultimediaObject
    {
        $multimediaObject->upTrackById($trackId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    public function downTrackInMultimediaObject(MultimediaObject $multimediaObject, string $trackId): MultimediaObject
    {
        $multimediaObject->downTrackById($trackId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    public function getTempDirs(): array
    {
        return [$this->tmpPath];
    }

    private function deleteFileOnDisk(string $path): void
    {
        $dirname = pathinfo($path, PATHINFO_DIRNAME);

        try {
            $deleted = unlink($path);
            if (!$deleted) {
                throw new \Exception("Error deleting file '".$path."' on disk");
            }
            $finder = new Finder();
            $finder->files()->in($dirname);
            if (0 === $finder->count()) {
                $dirDeleted = rmdir($dirname);
                if (!$dirDeleted) {
                    throw new \Exception("Error deleting directory '".$dirname."'on disk");
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    private function countMultimediaObjectWithTrack(string $trackPath): int
    {
        $enableFilter = false;
        if ($this->dm->getFilterCollection()->isEnabled('backoffice')) {
            $enableFilter = true;
            $this->dm->getFilterCollection()->disable('backoffice');
        }

        $otherTracks = $this->dm->getRepository(MultimediaObject::class)->findBy(['tracks.path' => $trackPath]);
        if ($enableFilter) {
            $this->dm->getFilterCollection()->enable('backoffice');
        }

        return count($otherTracks);
    }
}
