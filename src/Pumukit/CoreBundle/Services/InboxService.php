<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Services;

class InboxService
{
    private $inboxUploadURL;
    private $inboxUploadLIMIT;
    private $minFileSize;
    private $maxFileSize;
    private $maxNumberOfFiles;
    private $inboxPath;
    private $debug;
    private $overridePatchMethod;

    public function __construct(
        string $inboxUploadURL,
        int $inboxUploadLIMIT,
        string $minFileSize,
        string $maxFileSize,
        int $maxNumberOfFiles,
        string $inboxPath,
        bool $debug,
        bool $overridePatchMethod
    ) {
        $this->inboxUploadURL = $inboxUploadURL;
        $this->inboxUploadLIMIT = $inboxUploadLIMIT;
        $this->minFileSize = $minFileSize;
        $this->maxFileSize = $maxFileSize;
        $this->maxNumberOfFiles = $maxNumberOfFiles;
        $this->inboxPath = $inboxPath;
        $this->debug = $debug;
        $this->overridePatchMethod = $overridePatchMethod;
    }

    public function inboxUploadURL(): string
    {
        return $this->inboxUploadURL;
    }

    public function inboxUploadLIMIT(): int
    {
        return $this->inboxUploadLIMIT;
    }

    public function minFileSize(): string
    {
        return $this->minFileSize;
    }

    public function maxFileSize(): string
    {
        return $this->maxFileSize;
    }

    public function maxNumberOfFiles(): int
    {
        return $this->maxNumberOfFiles;
    }

    public function inboxPath(): string
    {
        return $this->inboxPath;
    }

    public function debug(): bool
    {
        return $this->debug;
    }

    public function overridePatchMethod(): bool
    {
        return $this->overridePatchMethod;
    }
}
