<?php

declare(strict_types=1);

namespace Pumukit\InspectionBundle\Services;

use Psr\Log\LoggerInterface;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Symfony\Component\Process\Process;

class InspectionFfprobeService implements InspectionServiceInterface
{
    private $logger;
    private $command;

    public function __construct(string $command = null, LoggerInterface $logger = null)
    {
        $this->command = $command ?: 'ffprobe -v quiet -print_format json -show_format -show_streams "{{file}}"';
        $this->logger = $logger;
    }

    /**
     * Gets file duration in s. Check "mediainfo -f file" output.
     */
    public function getDuration(string $file): int
    {
        if (!file_exists($file)) {
            throw new \BadMethodCallException('The file '.$file.' does not exist');
        }

        $json = json_decode($this->getMediaInfo($file), false, 512, JSON_THROW_ON_ERROR);
        if (!$this->jsonHasMediaContent($json)) {
            throw new \InvalidArgumentException('This file has no accessible video '."nor audio tracks\n".$file);
        }

        $duration = 0;
        if (isset($json->format->duration)) {
            $duration = (int) ceil((float) $json->format->duration);
        }

        return $duration;
    }

    public function getFileMetadata(?Path $path)
    {
        if (!$path->path()) {
            throw new \BadMethodCallException('Input track has no path defined');
        }

        $json = json_decode(
            $this->getMediaInfo($path->path()),
            false,
            512,
            JSON_THROW_ON_ERROR
        );
        if (!$this->jsonHasMediaContent($json)) {
            throw new \InvalidArgumentException('This file has no accesible video '."nor audio tracks\n".$path->path());
        }

        return $json;
    }

    public function getFileMetadataAsString(?Path $path): string
    {
        return json_encode($this->getFileMetadata($path), JSON_THROW_ON_ERROR);
    }

    private function jsonHasMediaContent($json): bool
    {
        if (isset($json->streams)) {
            foreach ($json->streams as $stream) {
                if (isset($stream->codec_type, $stream->codec_name) && ('audio' === $stream->codec_type || 'video' === $stream->codec_type) && ('ansi' !== $stream->codec_name)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getMediaInfo(string $file): string
    {
        $command = str_replace('{{file}}', $file, $this->command);
        $command = str_replace('"', "'", $command);

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            $message = 'Exception executing "'.$command.'": '.$process->getExitCode().' '.
              $process->getExitCodeText().'. '.$process->getErrorOutput();
            if ($this->logger) {
                $this->logger->error($message);
            }
        }

        return $process->getOutput();
    }
}
