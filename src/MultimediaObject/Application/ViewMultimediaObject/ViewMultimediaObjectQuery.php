<?php

namespace App\MultimediaObject\Application\ViewMultimediaObject;

final class ViewMultimediaObjectQuery
{
    public function __construct(private string $id) {}

    public function id(): string
    {
        return $this->id;
    }
}
