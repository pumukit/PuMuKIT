<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\MediaType;

use Pumukit\SchemaBundle\Document\MediaType\Metadata\Exif;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\MediaMetadata;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;

interface MediaInterface
{
    public function id(): string;

    public function getId(): string;

    public function originalName(): string;

    public function getOriginalName(): string;

    public function description(): i18nText;

    public function getDescription(): i18nText;

    public function language(): ?string;

    public function getLanguage(): ?string;

    public function tags(): Tags;

    public function getTags(): array;

    public function isHide(): bool;

    public function isVisible(): bool;

    public function isDownloadable(): bool;

    public function views(): int;

    public function getViews(): int;

    public function storage(): Storage;

    public function getUrl(): ?string;

    public function metadata(): VideoAudio;

    public function getDuration(): int;

    public function videoMetadata(): VideoAudio;

    public function imageMetadata(): Exif;

    public function documentMetadata(): Exif;

    public function isMaster(): bool;

    public function profileName(): ?string;

    public function updateId(string $id): void;

    public function updateTags(Tags $tags): void;

    public function updateStorage(Storage $storage): void;

    public function updateDescription(i18nText $description): void;

    public function updateLanguage(string $language): void;

    public function changeHide(): void;

    public function updateHide(bool $hide): void;

    public function updateDownload(bool $download): void;

    public function mimeType(): string;

    public function updateMetadata(MediaMetadata $metadata): void;
}
