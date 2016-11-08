<?php

namespace Pumukit\InspectionBundle\Services;

use Pumukit\SchemaBundle\Document\Track;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class InspectionFfmpegService implements InspectionServiceInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        if (!class_exists('ffmpeg_movie')) {
            throw new \LogicException("ffmpeg_movie is not loaded");
        }
        $this->logger = $logger;
    }

    // TO DO check if the duration is rounded up
    /**
     * Gets file duration in s.
     * @param $file
     * @return integer $duration file duration in s rounded up.
     */
    public function getDuration($file)
    {
        if (!file_exists($file)) {
            throw new \BadMethodCallException("The file " . $file . " does not exist");
        }

        $movie = new \ffmpeg_movie($file, false);
        $finfo = new \finfo();

        if (!$this->fileHasMediaContent($finfo, $file)) {
            throw new \InvalidArgumentException("This file has no video nor audio tracks");
        }

        return ceil($movie->getDuration());
    }

    /**
     * Completes track information from a given path using ffmpeg.
     * @param Track $track
     */
    public function autocompleteTrack(Track $track)
    {
        if (!$track->getPath()) {
            throw new \BadMethodCallException('Input track has no path defined');
        }
        $file = $track->getPath();
        $movie = new \ffmpeg_movie($file, false);
        $finfo = new \finfo();

        if (!$this->fileHasMediaContent($finfo, $file)) {
            throw new \InvalidArgumentException("This file has no video nor audio tracks");
        }

        $only_audio = true;

        // General
        $track->setMimetype($finfo->file($file, FILEINFO_MIME_TYPE));
        $track->setBitrate($movie->getBitRate());
        $track->setDuration(ceil($movie->getDuration()));
        $track->setSize(filesize($file));

        if ($movie->hasVideo()) {
            $only_audio = false;
            $track->setVcodec($movie->getVideoCodec());
            $track->setFramerate($movie->getFrameRate());
            $track->setWidth($movie->getFrameWidth());
            $track->setHeight($movie->getFrameHeight());
        }

        if ($movie->hasAudio()) {
            $track->setAcodec($movie->getAudioCodec());
            $track->setChannels($movie->getAudioChannels());
        }

        $track->setOnlyAudio($only_audio);
    }

    private function fileHasMediaContent($finfo, $file)
    {
        $mime = substr($finfo->file($file, FILEINFO_MIME_TYPE), 0, 5);
        if ($mime == "audio" || $mime == "video") {
            return true;
        }

        return false;
    }
}
