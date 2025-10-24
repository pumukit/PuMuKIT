<?php

namespace App\MultimediaObject\Application\ViewMultimediaObject;

final class ViewMultimediaObjectQuery
{
    public function __construct(private string $id, private string $tab = 'general') {}

    public function id(): string
    {
        return $this->id;
    }

    public function tab(): string
    {
        return $this->tab;
    }
}
