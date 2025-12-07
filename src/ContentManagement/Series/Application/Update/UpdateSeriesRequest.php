<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Update;

final class UpdateSeriesRequest
{
    public function __construct(
        public readonly string $id,
        public readonly ?array $title = null,
        public readonly ?array $subtitle = null,
        public readonly ?array $description = null,
        public readonly ?array $header = null,
        public readonly ?array $footer = null,
        public readonly ?string $comments = null,
        public readonly ?array $keywords = null,
        public readonly ?bool $announce = null,
        public readonly ?bool $hide = null,
        public readonly ?\DateTimeInterface $publicDate = null,
        public readonly ?int $sorting = null,
        public readonly ?string $seriesTypeId = null,
        public readonly ?string $seriesStyleId = null,
        public readonly ?array $properties = null
    ) {
        UpdateSeriesValidator::validate($this);
    }
}
