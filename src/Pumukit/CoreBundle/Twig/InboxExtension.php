<?php

namespace Pumukit\CoreBundle\Twig;

use Pumukit\CoreBundle\Services\InboxService;
use Pumukit\CoreBundle\Utils\MediaMimeTypeUtils;
use Pumukit\SchemaBundle\Document\MultimediaObject;
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
            new TwigFunction('inbox_progress_bar_color', [$this, 'getProgressBarColor']),
            new TwigFunction('inbox_show_backoffice_button', [$this, 'getShowBackofficeButtonInInbox']),
            new TwigFunction('filter_valid_types_of_files', [$this, 'getFilteredTypesOfFiles']),
            new TwigFunction('allowed_type_files', [$this, 'getAllowedTypeFiles']),
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

    public function getProgressBarColor(): string
    {
        return $this->inboxService->progressBarColor();
    }

    public function getShowBackofficeButtonInInbox(): bool
    {
        return $this->inboxService->showBackofficeButtonInInbox();
    }

    public function getFilteredTypesOfFiles(MultimediaObject $multimediaObject): string
    {
        if ($multimediaObject->isAudioType()) {
            return json_encode(MediaMimeTypeUtils::allowedAudioMimeTypes(), JSON_THROW_ON_ERROR);
        }

        if ($multimediaObject->isVideoType()) {
            return json_encode(MediaMimeTypeUtils::allowedVideoMimeTypes(), JSON_THROW_ON_ERROR);
        }

        if ($multimediaObject->isImageType()) {
            return json_encode(MediaMimeTypeUtils::allowedImageMimeTypes(), JSON_THROW_ON_ERROR);
        }

        if ($multimediaObject->isDocumentType()) {
            return json_encode(MediaMimeTypeUtils::allowedDocumentMimeTypes(), JSON_THROW_ON_ERROR);
        }

        throw new \Exception('Invalid type of multimedia object');
    }

    public function getAllowedTypeFiles(): string
    {
        $allowedTypes = MediaMimeTypeUtils::allowedMimeTypes();

        return json_encode($allowedTypes, JSON_THROW_ON_ERROR);
    }
}
