<?php

namespace Pumukit\CoreBundle\Twig;

use Pumukit\CoreBundle\Services\InboxService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class InboxExtension extends AbstractExtension
{
    private $inboxService;

    public function __construct(InboxService $inboxService)
    {
        $this->inboxService = $inboxService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('inbox_upload_url', [$this, 'getInboxUploadURL']),
            new TwigFunction('inbox_upload_limit', [$this, 'getInboxUploadLIMIT']),
            new TwigFunction('inbox_min_file_size', [$this, 'getInboxMinFileSize']),
            new TwigFunction('inbox_max_file_size', [$this, 'getInboxMaxFileSize']),
            new TwigFunction('inbox_max_number_of_files', [$this, 'getMaxNumberOfFiles']),
            new TwigFunction('inbox_path', [$this, 'getInboxPath']),
            new TwigFunction('inbox_debug', [$this, 'getInboxDebug']),
            new TwigFunction('inbox_override_patch_method', [$this, 'getOverridePatchMethod']),
        ];
    }

    public function getInboxUploadURL(): string
    {
        return $this->inboxService->inboxUploadURL();
    }

    public function getInboxUploadLIMIT(): int
    {
        return $this->inboxService->inboxUploadLIMIT();
    }

    public function getInboxMinFileSize(): string
    {
        return $this->inboxService->minFileSize();
    }

    public function getInboxMaxFileSize(): string
    {
        return $this->inboxService->maxFileSize();
    }

    public function getMaxNumberOfFiles(): int
    {
        return $this->inboxService->maxNumberOfFiles();
    }

    public function getInboxPath(): string
    {
        return $this->inboxService->inboxPath();
    }

    public function getInboxDebug(): string
    {
        return true == $this->inboxService->debug() ? 'true' : 'false';
    }

    public function getOverridePatchMethod(): string
    {
        return true == $this->inboxService->overridePatchMethod() ? 'true' : 'false';
    }
}
