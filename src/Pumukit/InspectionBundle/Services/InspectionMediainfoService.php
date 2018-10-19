<?php

namespace Pumukit\InspectionBundle\Services;

use Pumukit\SchemaBundle\Document\Track;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class InspectionMediainfoService implements InspectionServiceInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger = null)
    {
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

        $xml = simplexml_load_string($this->getMediaInfo($file));
        if (!$this->xmlHasMediaContent($xml)) {
            throw new \InvalidArgumentException('This file has no accesible video '.
                "nor audio tracks\n".$file);
        }

        $duration = ceil($xml->File->track->Duration / 1000); // in ms (using mediainfo -f)

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

        $xml = simplexml_load_string($this->getMediaInfo($track->getPath()));
        if (!$this->xmlHasMediaContent($xml)) {
            throw new \InvalidArgumentException('This file has no accesible video '.
                "nor audio tracks\n".$track->getPath());
        }

        foreach ($xml->File->track as $xml_track) {
            switch ((string) $xml_track['type']) {
                case 'General':
                    $track->setMimetype($xml_track->Internet_media_type);
                    $track->setBitrate(intval($xml_track->Overall_bit_rate[0]));
                    $aux = intval((string) $xml_track->Duration[0]);
                    $track->setDuration(ceil($aux / 1000));
                    $track->setSize((string) $xml_track->File_size[0]);
                    break;

                case 'Video':
                    $track->setVcodec((string) $xml_track->Format[0]);
                    $track->setFramerate((string) $xml_track->Frame_rate[0]);
                    $track->setWidth(intval($xml_track->Width));
                    $track->setHeight(intval($xml_track->Height));
                    $only_audio = false;
                    break;

                case 'Audio':
                    $track->setAcodec((string) $xml_track->Format[0]);
                    $track->setChannels(intval($xml_track->Channel_s_));
                    break;
            }
            $track->setOnlyAudio($only_audio);
        }
    }

    private function xmlHasMediaContent($xml)
    {
        //var_dump($xml->media->track);
        if (null !== $xml->File->track) {
            foreach ($xml->File->track as $track) {
                if ('Audio' == $track['type'] || 'Video' == $track['type']) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getMediaInfo($file)
    {
        $command = 'mediainfo -f --Output=XML \''.$file.'\'';
        $process = new Process($command);
        $process->setEnv(array('LANG' => 'en_US.UTF-8'));
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
