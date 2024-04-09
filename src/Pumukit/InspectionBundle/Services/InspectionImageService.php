<?php

declare(strict_types=1);

namespace Pumukit\InspectionBundle\Services;

use Psr\Log\LoggerInterface;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Symfony\Component\Process\Process;

final class InspectionImageService implements InspectionServiceInterface
{
    private string $command;
    private LoggerInterface $logger;

    public function __construct(string $command = null, LoggerInterface $logger = null)
    {
        $this->command = $command ?: 'exiftool -json "{{file}}"';
        $this->logger = $logger;
    }

    public function getFileMetadata(?Path $path): string
    {
        if (!$path->path()) {
            throw new \BadMethodCallException('Input media has no path defined');
        }

        return $this->getMediaInfo($path->path());
    }

    public function getFileMetadataAsString(?Path $path): string
    {
        return $this->getFileMetadata($path);
    }

    private function getMediaInfo(string $file): string
    {
        $command = str_replace('{{file}}', $file, $this->command);
        $command = str_replace('"', "'", $command);

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            $message = 'Exception executing "'.$command.'": '.$process->getExitCode().' '. $process->getExitCodeText().'. '.$process->getErrorOutput();
            $this->logger->error($message);
        }

        return $process->getOutput();
    }
}
