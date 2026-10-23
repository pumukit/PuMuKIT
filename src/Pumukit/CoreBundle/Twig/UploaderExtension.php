<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Twig;

use Pumukit\CoreBundle\Services\InboxService;
use Pumukit\CoreBundle\Utils\MediaMimeTypeUtils;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UploaderExtension extends AbstractExtension
{
    private const NESTED_OPTIONS = ['restrictions', 'tus', 'xhr', 'import', 'dashboard'];
    private const STYLES = ['vendor/@uppy/core/dist/style.min.css', 'vendor/@uppy/dashboard/dist/style.min.css'];

    public function __construct(
        private readonly InboxService $inboxService,
        private readonly RequestStack $requestStack,
        private readonly Packages $packages,
        private readonly string $locale,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('uploader_attributes', [$this, 'attributes'], ['is_safe' => ['html']]),
            new TwigFunction('uploader_options', [$this, 'options']),
            new TwigFunction('uploader_allowed_types', [$this, 'allowedTypes']),
        ];
    }

    public function attributes(array $options = []): string
    {
        return sprintf(
            'data-controller="core--uploader" data-core--uploader-options-value="%s"',
            htmlspecialchars(json_encode($this->options($options), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES)
        );
    }

    public function options(array $options = []): array
    {
        $transport = $options['transport'] ?? 'tus';
        $defaults = [
            'transport' => $transport,
            'endpoint' => 'tus' === $transport ? $this->inboxService->inboxUploadURL() : '',
            'autoProceed' => 'tus' === $transport,
            'locale' => $this->requestStack->getCurrentRequest()?->getLocale() ?? $this->locale,
            'debug' => $this->inboxService->debug(),
            'renameFiles' => false,
            'formMeta' => false,
            'meta' => [],
            'restrictions' => [
                'minFileSize' => self::sizeToBytes($this->inboxService->minFileSize()),
                'maxFileSize' => self::sizeToBytes($this->inboxService->maxFileSize()),
                'maxNumberOfFiles' => $this->inboxService->maxNumberOfFiles(),
                'allowedFileTypes' => MediaMimeTypeUtils::allowedMimeTypes(),
            ],
            'tus' => [
                'overridePatchMethod' => $this->inboxService->overridePatchMethod(),
                'limit' => $this->inboxService->inboxUploadLIMIT(),
            ],
            'xhr' => [
                'fieldName' => 'files[]',
                'limit' => 10,
                'timeout' => 1800000,
            ],
            'dashboard' => null,
            'import' => null,
            'progressColor' => $this->inboxService->progressBarColor(),
            'styles' => array_map(fn (string $path): string => $this->packages->getUrl($path), self::STYLES),
        ];

        $merged = array_replace($defaults, $options);
        foreach (self::NESTED_OPTIONS as $key) {
            if (is_array($defaults[$key]) && is_array($options[$key] ?? null)) {
                $merged[$key] = array_replace($defaults[$key], $options[$key]);
            }
        }

        return $merged;
    }

    public function allowedTypes(?MultimediaObject $multimediaObject = null): array
    {
        return match (true) {
            null === $multimediaObject => MediaMimeTypeUtils::allowedMimeTypes(),
            $multimediaObject->isAudioType() => MediaMimeTypeUtils::allowedAudioMimeTypes(),
            $multimediaObject->isVideoType() => MediaMimeTypeUtils::allowedVideoMimeTypes(),
            $multimediaObject->isImageType() => MediaMimeTypeUtils::allowedImageMimeTypes(),
            $multimediaObject->isDocumentType() => MediaMimeTypeUtils::allowedDocumentMimeTypes(),
            default => throw new \InvalidArgumentException('Invalid type of multimedia object'),
        };
    }

    public static function sizeToBytes(string $size): int
    {
        if (str_contains($size, 'GB')) {
            return (int) str_replace('GB', '', $size) * 1024 * 1024 * 1024;
        }

        if (str_contains($size, 'MB')) {
            return (int) str_replace('MB', '', $size) * 1024 * 1024;
        }

        return 1024 * 1024 * 1024;
    }
}
