<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\MediaType;

use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;

interface Media
{
    public static function create(
        string        $originalName,
        i18nText      $description,
        Tags          $tags,
        bool          $hide,
        bool          $isDownloadable,
        int           $views,
        Storage       $storage,
        MediaMetadata $mediaMetadata
    ): Media;

    public function id();

    public function type(): int;

    public function __toString(): string;

    public function originalName(): string;

    public function description(): i18nText;

    public function isHide(): bool;

    public function tags(): Tags;

    public function isDownloadable(): bool;

    public function views(): int;

    public function storage(): Storage;

    public function metadata(): MediaMetadata;
}
