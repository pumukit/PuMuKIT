<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\View;

final class ViewMultimediaObjectRequest
{
    public function __construct(
        public string $id,
        public string $tab = 'general'
    ) {}
}
