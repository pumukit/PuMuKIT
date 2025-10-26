<?php

namespace App\MultimediaObject\Application\View;

final class ViewMultimediaObjectResponse
{
    public function __construct(
        private $object,
        private string $tab
    ) {}

    public function object()
    {
        return $this->object;
    }

    public function tab(): string
    {
        return $this->tab;
    }
}
