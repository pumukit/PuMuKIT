<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\View;

final class ViewMultimediaObjectRequest
{
    public function __construct(
        public readonly string $id,
        public readonly string $tab = 'general'
    ) {}
}
