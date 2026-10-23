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
    private $progressBarColor;
    private $showBackofficeButtonInInbox;

    public function __construct(
        string $inboxUploadURL,
        int $inboxUploadLIMIT,
        string $minFileSize,
        string $maxFileSize,
        int $maxNumberOfFiles,
        string $inboxPath,
        bool $debug,
        bool $overridePatchMethod,
        string $progressBarColor,
        bool $showBackofficeButtonInInbox
    ) {
        $this->inboxUploadURL = $inboxUploadURL;
        $this->inboxUploadLIMIT = $inboxUploadLIMIT;
        $this->minFileSize = $minFileSize;
        $this->maxFileSize = $maxFileSize;
        $this->maxNumberOfFiles = $maxNumberOfFiles;
        $this->inboxPath = $inboxPath;
        $this->debug = $debug;
        $this->overridePatchMethod = $overridePatchMethod;
        $this->progressBarColor = $progressBarColor;
        $this->showBackofficeButtonInInbox = $showBackofficeButtonInInbox;
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

    public function progressBarColor(): string
    {
        return $this->progressBarColor;
    }

    public function showBackofficeButtonInInbox(): bool
    {
        return $this->showBackofficeButtonInInbox;
    }

    public function folderName(string $folder): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9_ -]/', '', $folder);
        if ('' === trim((string) $clean)) {
            throw new \InvalidArgumentException('Folder name must contain at least one valid character (letters, numbers, underscores, or dashes).');
        }

        return $clean;
    }

    public function uploadedFilePath(string $fileName, string $folder): string
    {
        if ('' === $fileName || $fileName !== basename($fileName) || str_contains($fileName, "\0") || in_array($fileName, ['.', '..'], true)) {
            throw new \InvalidArgumentException('Invalid file name.');
        }

        $inboxPath = realpath($this->inboxPath);
        if (!$inboxPath) {
            throw new \InvalidArgumentException('The inbox directory does not exist.');
        }

        $directory = $inboxPath;

        try {
            $folderPath = $inboxPath.DIRECTORY_SEPARATOR.$this->folderName($folder);
            if (is_dir($folderPath)) {
                $directory = $folderPath;
            }
        } catch (\InvalidArgumentException) {
        }

        $filePath = realpath($directory.DIRECTORY_SEPARATOR.$fileName);
        if (!$filePath || !is_file($filePath) || !str_starts_with($filePath, $inboxPath.DIRECTORY_SEPARATOR)) {
            throw new \InvalidArgumentException('The file is not in the inbox.');
        }

        return $filePath;
    }
}
