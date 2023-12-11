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
    private $url;
    private $path;
    private $storageSystem;

    private function __construct(Url $url, Path $path = null)
    {
        $this->url = $url;
        $this->path = $path;
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
        return $this->storageSystem === self::S3_STORAGE;
    }
    public function isLocalStorageSystem(): bool
    {
        return $this->storageSystem === self::LOCAL_STORAGE;
    }
    public function isExternalStorageSystem(): bool
    {
        return $this->storageSystem === self::EXTERNAL_STORAGE;
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
