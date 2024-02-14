<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\MediaType;

use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\Url;


final class Storage
{
    public const LOCAL_STORAGE = 1;
    public const S3_STORAGE = 2;
    public const EXTERNAL_STORAGE = 9;
    private ?Url $url;
    private ?Path $path;
    private int $storageSystem;

    private function __construct(Url $url = null, Path $path = null)
    {
        $this->url = $url;
        $this->path = $path;
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url?->url(),
            'path' => $this->path?->path(),
            'storageSystem' => $this->storageSystem,
        ];
    }

    public function url(): Url
    {
        return $this->url;
    }

    public function path(): Path
    {
        return $this->path;
    }

    public function storageSystem(): int
    {
        return $this->storageSystem;
    }

    public function isS3StorageSystem(): bool
    {
        return self::S3_STORAGE === $this->storageSystem;
    }

    public function isLocalStorageSystem(): bool
    {
        return self::LOCAL_STORAGE === $this->storageSystem;
    }

    public function isExternalStorageSystem(): bool
    {
        return self::EXTERNAL_STORAGE === $this->storageSystem;
    }

    public static function create(?Url $url, ?Path $path): Storage
    {
        if (!$url && !$path) {
            throw new \Exception('Url and path cannot be null both');
        }

        if (!$path instanceof Path) {
            return self::external($url);
        }

        if (str_contains($url->url(), 's3')) {
            return self::s3($url, $path);
        }

        return self::local($url, $path);
    }

    public static function local(Url $url, Path $path): Storage
    {
        $storage = new self($url, $path);
        $storage->storageSystem = self::LOCAL_STORAGE;

        return $storage;
    }

    public static function external(Url $url): Storage
    {
        $storage = new self($url);
        $storage->storageSystem = self::EXTERNAL_STORAGE;

        return $storage;
    }

    public static function s3(Url $url, Path $path): Storage
    {
        $storage = new self($url, $path);
        $storage->storageSystem = self::S3_STORAGE;

        return $storage;
    }
}
