<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Component\Finder\Finder;

class TrackService
{
    private $dm;
    private $dispatcher;
    private $tmpPath;
    private $profileService;
    private $forceDeleteOnDisk;

    public function __construct(DocumentManager $documentManager, TrackEventDispatcherService $dispatcher, ProfileService $profileService, $tmpPath = null, $forceDeleteOnDisk = true)
    {
        $this->dm = $documentManager;
        $this->dispatcher = $dispatcher;
        $this->profileService = $profileService;
        $this->tmpPath = $tmpPath ? realpath($tmpPath) : sys_get_temp_dir();
        $this->forceDeleteOnDisk = $forceDeleteOnDisk;
    }

    /**
     * Add track to multimedia object.
     *
     * @param MultimediaObject $multimediaObject
     * @param Track            $track
     * @param bool             $executeFlush
     *
     * @return MultimediaObject
     */
    public function addTrackToMultimediaObject(MultimediaObject $multimediaObject, Track $track, $executeFlush = true)
    {
        $multimediaObject->addTrack($track);

        if ($executeFlush) {
            $this->dm->persist($multimediaObject);
            $this->dm->flush();
        }

        $this->dispatcher->dispatchCreate($multimediaObject, $track);

        return $multimediaObject;
    }

    /**
     * Update Track in Multimedia Object.
     */
    public function updateTrackInMultimediaObject(MultimediaObject $multimediaObject, Track $track)
    {
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->dispatcher->dispatchUpdate($multimediaObject, $track);

        return $multimediaObject;
    }

    /**
     * Remove Track from Multimedia Object.
     *
     * @param mixed $trackId
     */
    public function removeTrackFromMultimediaObject(MultimediaObject $multimediaObject, $trackId)
    {
        $track = $multimediaObject->getTrackById($trackId);
        $trackPath = $track->getPath();

        $isNotOpencast = !$track->containsTag('opencast');

        $multimediaObject->removeTrackById($trackId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        if ($this->forceDeleteOnDisk && $trackPath && $isNotOpencast) {
            $countOtherTracks = $this->countMultimediaObjectWithTrack($trackPath);

            if (0 == $countOtherTracks) {
                $this->deleteFileOnDisk($trackPath);
            }
        }

        $this->dispatcher->dispatchDelete($multimediaObject, $track);

        return $multimediaObject;
    }

    /**
     * Up Track in Multimedia Object.
     *
     * @param mixed $trackId
     */
    public function upTrackInMultimediaObject(MultimediaObject $multimediaObject, $trackId)
    {
        $multimediaObject->upTrackById($trackId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Down Track in Multimedia Object.
     *
     * @param mixed $trackId
     */
    public function downTrackInMultimediaObject(MultimediaObject $multimediaObject, $trackId)
    {
        $multimediaObject->downTrackById($trackId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Get temp directories.
     */
    public function getTempDirs()
    {
        return [$this->tmpPath];
    }

    private function deleteFileOnDisk($path)
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

    private function countMultimediaObjectWithTrack($trackPath)
    {
        $enableFilter = false;
        if ($this->dm->getFilterCollection()->isEnabled('backoffice')) {
            $enableFilter = true;
            $filter = $this->dm->getFilterCollection()->disable('backoffice');
        }

        $mmobjRepo = $this->dm->getRepository(MultimediaObject::class);
        $otherTracks = $mmobjRepo->findBy(['tracks.path' => $trackPath]);
        if ($enableFilter) {
            $filter = $this->dm->getFilterCollection()->enable('backoffice');
        }

        return count($otherTracks);
    }
}
