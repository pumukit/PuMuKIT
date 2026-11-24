<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Services;

use Psr\Log\LoggerInterface;
use Pumukit\SchemaBundle\Document\Exception\PathException;
use Pumukit\SchemaBundle\Document\MediaType\Media;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class TrackDeliveryService
{
    public const REASON_OUTSIDE_STORAGE = 'outside the storage';
    public const REASON_MISSING_FILE = 'missing file';
    public const REASON_SHARED_HLS_DIRECTORY = 'shared HLS directory';
    public const REASON_NO_DOWNLOAD_SERVER = 'no download server';

    private const RESERVED_PREFIXES = ['files', 'hls', 'pumukit'];

    private readonly array $extraRoots;

    public function __construct(
        private readonly string $storageDir,
        private readonly ?string $downloadServerUrl,
        private readonly SecureTokenService $secureTokenService,
        private readonly TrackUrlService $trackUrlService,
        private readonly LoggerInterface $logger,
        ?string $extraRoots = null
    ) {
        $this->extraRoots = $this->parseExtraRoots((string) $extraRoots);
    }

    public function response(Media $track, bool $forceDownload = false, ?string $externalQuery = null): Response
    {
        if ($this->isExternal($track)) {
            return $this->externalResponse($track, $forceDownload, $externalQuery);
        }

        $reason = $this->undeliverableReason($track);
        if (null !== $reason) {
            if (self::REASON_MISSING_FILE === $reason) {
                $this->logger->error('Track file not found', [
                    'track' => $track->id(),
                    'path' => $this->localPath($track),
                ]);

                return new Response('Not Found', Response::HTTP_NOT_FOUND);
            }

            $this->logger->critical('Track cannot be delivered by the download server', [
                'track' => $track->id(),
                'path' => $this->localPath($track),
                'reason' => $reason,
            ]);

            return new Response('Service Unavailable', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new RedirectResponse((string) $this->downloadServerUrl($track, $forceDownload));
    }

    public function downloadServerUrl(Media $track, bool $forceDownload = false): ?string
    {
        if ($this->isExternal($track)) {
            $url = $this->externalUrl($track);
            if (null === $url || !$this->trackUrlService->isOnDownloadServer($url)) {
                return null;
            }

            return $this->forceDownload($this->secureTokenService->generateDownloadUrl($url, $this->validFor($track)), $forceDownload);
        }

        if (null !== $this->undeliverableReason($track)) {
            return null;
        }

        $realPath = (string) realpath($this->localPath($track));
        $validFor = $this->validFor($track);
        $baseUrl = rtrim((string) $this->downloadServerUrl, '/');

        $extraRoot = $this->isInStorage($realPath) ? null : $this->extraRootOf($realPath);
        if (null !== $extraRoot) {
            $relative = substr($realPath, strlen($extraRoot['root']) + 1);
            $encoded = implode('/', array_map('rawurlencode', explode('/', $relative)));

            return $this->forceDownload($this->secureTokenService->generateDownloadUrl($baseUrl.$extraRoot['prefix'].'/'.$encoded, $validFor), $forceDownload);
        }

        $relative = substr($realPath, strlen($this->realStorageDir()) + 1);

        if ($this->isHls($realPath)) {
            $segments = explode('/', $relative);

            return $this->secureTokenService->generateHlsUrl(
                $baseUrl,
                sprintf('/files/downloads/%s/%s/', $segments[1], $segments[2]),
                $segments[3],
                $validFor
            );
        }

        $encoded = implode('/', array_map('rawurlencode', explode('/', $relative)));
        $signed = $this->secureTokenService->generateDownloadUrl($baseUrl.'/files/'.$encoded, $validFor);

        return $this->forceDownload($signed, $forceDownload);
    }

    public function undeliverableReason(Media $track): ?string
    {
        if ($this->isExternal($track)) {
            return null;
        }

        if (empty($this->downloadServerUrl)) {
            return self::REASON_NO_DOWNLOAD_SERVER;
        }

        $realPath = realpath($this->localPath($track));
        if (false === $realPath || !is_file($realPath)) {
            return self::REASON_MISSING_FILE;
        }

        if (!$this->isInStorage($realPath)) {
            return null === $this->extraRootOf($realPath) || $this->isHls($realPath) ? self::REASON_OUTSIDE_STORAGE : null;
        }

        if ($this->isHls($realPath) && !$this->isInOwnHlsDirectory($realPath)) {
            return self::REASON_SHARED_HLS_DIRECTORY;
        }

        return null;
    }

    public function isInOwnHlsDirectory(string $path): bool
    {
        $realPath = realpath($path);
        if (false === $realPath || !$this->isHls($realPath)) {
            return false;
        }

        $prefix = $this->realStorageDir().'/downloads/';
        if (!str_starts_with($realPath, $prefix)) {
            return false;
        }

        $segments = explode('/', substr($realPath, strlen($prefix)));

        return 3 === count($segments)
            && 1 === preg_match('/^[A-Za-z0-9_-]+$/', $segments[0])
            && 1 === preg_match('/^[A-Za-z0-9_-]+$/', $segments[1])
            && $segments[1].'.m3u8' === $segments[2];
    }

    public function localPath(Media $track): string
    {
        try {
            return $track->storage()->path()?->path() ?? '';
        } catch (PathException) {
            return (string) $track->getUrl();
        }
    }

    private function isExternal(Media $track): bool
    {
        return '' === $this->localPath($track);
    }

    private function externalUrl(Media $track): ?string
    {
        $url = $track->storage()->url()?->url();

        return empty($url) ? null : $url;
    }

    private function realStorageDir(): string
    {
        return rtrim((string) realpath($this->storageDir), '/');
    }

    private function isInStorage(string $realPath): bool
    {
        $root = $this->realStorageDir();

        return '' !== $root && (str_starts_with($realPath, $root.'/downloads/') || str_starts_with($realPath, $root.'/masters/'));
    }

    private function extraRootOf(string $realPath): ?array
    {
        foreach ($this->extraRoots as $extraRoot) {
            $root = realpath($extraRoot['root']);
            if (false !== $root && str_starts_with($realPath, rtrim($root, '/').'/')) {
                return ['root' => rtrim($root, '/'), 'prefix' => $extraRoot['prefix']];
            }
        }

        return null;
    }

    private function parseExtraRoots(string $value): array
    {
        $extraRoots = [];
        foreach (explode(',', $value) as $entry) {
            $entry = trim($entry);
            if ('' === $entry) {
                continue;
            }

            $parts = array_map('trim', explode('=', $entry, 2));
            $root = rtrim($parts[0], '/');
            $prefix = $parts[1] ?? '';
            $firstSegment = explode('/', $prefix)[1] ?? '';

            if (!str_starts_with($root, '/')
                || 1 !== preg_match('#^(/[A-Za-z0-9_-]+)+$#', $prefix)
                || in_array($firstSegment, self::RESERVED_PREFIXES, true)
                || in_array($prefix, array_column($extraRoots, 'prefix'), true)) {
                throw new \InvalidArgumentException(sprintf('Invalid extra root "%s" of the download server: use "/absolute/path=/prefix", separated by commas, with a prefix that is not /files, /hls or /pumukit and is not repeated.', $entry));
            }

            $extraRoots[] = ['root' => $root, 'prefix' => $prefix];
        }

        return $extraRoots;
    }

    private function isHls(string $realPath): bool
    {
        return str_ends_with(strtolower($realPath), '.m3u8');
    }

    private function validFor(Media $track): int
    {
        return $this->secureTokenService->getTokenDuration() + $track->getDuration();
    }

    private function forceDownload(string $url, bool $forceDownload): string
    {
        return $forceDownload ? $url.'&forcedl=1' : $url;
    }

    private function externalResponse(Media $track, bool $forceDownload, ?string $externalQuery): Response
    {
        $url = $this->externalUrl($track);
        if (null === $url) {
            return new Response('Not Found', Response::HTTP_NOT_FOUND);
        }

        $signed = $this->downloadServerUrl($track, $forceDownload);
        if (null !== $signed) {
            return new RedirectResponse($signed);
        }

        if (!empty($externalQuery)) {
            $url .= (str_contains($url, '?') ? '&' : '?').$externalQuery;
        }

        return new RedirectResponse($url);
    }
}
