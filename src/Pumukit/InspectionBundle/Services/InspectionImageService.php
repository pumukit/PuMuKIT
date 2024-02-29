<?php

declare(strict_types=1);

namespace Pumukit\InspectionBundle\Services;

use Psr\Log\LoggerInterface;
use Pumukit\SchemaBundle\Document\ValueObject\Path;

final class InspectionImageService implements InspectionServiceInterface
{
    private string $command;
    private LoggerInterface $logger;

    public function __construct(string $command = null, LoggerInterface $logger = null)
    {
        $this->command = $command ?: 'ffprobe -v quiet -print_format json -show_format -show_streams "{{file}}"';
        $this->logger = $logger;
    }

    public function getFileMetadata(?Path $path)
    {

    }

    public function getFileMetadataAsString(?Path $path): string
    {
        return json_encode($this->getFileMetadata($path));
    }
}
