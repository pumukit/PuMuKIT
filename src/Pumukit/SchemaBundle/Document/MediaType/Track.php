<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\MediaType;

use Pumukit\SchemaBundle\Document\MediaType\Metadata\MediaMetadata;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument
 */
final class Track extends Media
{
    public static function create(
        string $originalName,
        i18nText $description,
        Tags $tags,
        bool $hide,
        bool $isDownloadable,
        int $views,
        Storage $storage,
        MediaMetadata $mediaMetadata
    ): MediaInterface {
        return new self($originalName, $description, $tags, $hide, $isDownloadable, $views, $storage, $mediaMetadata);
    }
}
