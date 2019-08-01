<?php

namespace Pumukit\InspectionBundle\Services;

use Psr\Log\LoggerInterface;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Component\Process\Process;

class InspectionFfprobeService implements InspectionServiceInterface
{
    private $logger;
    private $command;

    public function __construct($command = null, LoggerInterface $logger = null)
    {
        $this->command = $command ?: 'ffprobe -v quiet -print_format json -show_format -show_streams "{{file}}"';
        $this->logger = $logger;
    }

    /**
     * Gets file duration in s.
     * Check "mediainfo -f file" output.
     *
     * @param $file
     *
     * @return int $duration file duration in s rounded up
     */
    public function getDuration($file)
    {
        if (!file_exists($file)) {
            throw new \BadMethodCallException('The file '.$file.' does not exist');
        }

        $json = json_decode($this->getMediaInfo($file));
        if (!$this->jsonHasMediaContent($json)) {
            throw new \InvalidArgumentException('This file has no accesible video '.
                "nor audio tracks\n".$file);
        }

        $duration = 0;
        if (isset($json->format->duration)) {
            $duration = ceil((float) ($json->format->duration));
        }

        return $duration;
    }

    // Check the desired codec names (MPEG Audio/MPEG-1 Audio layer 3; AAC / Advanced Audio Codec / ...)
    // Now we choose FORMAT.

    /**
     * Completes track information from a given path using mediainfo.
     *
     * @param Track $track
     */
    public function autocompleteTrack(Track $track)
    {
        $only_audio = true; //initialized true until video track is found.
        if (!$track->getPath()) {
            throw new \BadMethodCallException('Input track has no path defined');
        }

        $json = json_decode($this->getMediaInfo($track->getPath()));
        if (!$this->jsonHasMediaContent($json)) {
            throw new \InvalidArgumentException('This file has no accesible video '.
                "nor audio tracks\n".$track->getPath());
        }

        $track->setMimetype(mime_content_type($track->getPath()));
        $bitrate = isset($json->format->bit_rate) ? (int) ($json->format->bit_rate) : 0;
        $track->setBitrate($bitrate);
        $duration = ceil((float) ($json->format->duration));
        $track->setDuration($duration);
        $size = isset($json->format->size) ? (string) $json->format->size : 0;
        $track->setSize($size);

        foreach ($json->streams as $stream) {
            if (isset($stream->codec_type)) {
                switch ((string) $stream->codec_type) {
                    case 'video':
                        if (isset($stream->codec_name)) {
                            $track->setVcodec((string) $stream->codec_name);
                        }
                        if (isset($stream->avg_frame_rate)) {
                            $track->setFramerate((string) $stream->avg_frame_rate);
                        }
                        if (isset($stream->width)) {
                            $track->setWidth((int) ($stream->width));
                        }
                        if (isset($stream->height)) {
                            $track->setHeight((int) ($stream->height));
                        }
                        $only_audio = false;

                        break;
                    case 'audio':
                        if (isset($stream->codec_name)) {
                            $track->setAcodec((string) $stream->codec_name);
                        }
                        if (isset($stream->channels)) {
                            $track->setChannels((int) ($stream->channels));
                        }

                        break;
                }
            }
            $track->setOnlyAudio($only_audio);
        }
    }

    private function jsonHasMediaContent($json)
    {
        if (null !== $json->streams) {
            foreach ($json->streams as $stream) {
                if ((isset($stream->codec_type, $stream->codec_name)) && ('audio' == $stream->codec_type || 'video' == $stream->codec_type) && ('ansi' != $stream->codec_name)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getMediaInfo($file)
    {
        $command = str_replace('{{file}}', $file, $this->command);
        $process = new Process($command);
        $process->setTimeout(60);
        $process->run();
        if (!$process->isSuccessful()) {
            $message = 'Exception executing "'.$command.'": '.$process->getExitCode().' '.
              $process->getExitCodeText().'. '.$process->getErrorOutput();
            if ($this->logger) {
                $this->logger->error($message);
            }

            throw new \RuntimeException($message);
        }

        return $process->getOutput();
    }
}
