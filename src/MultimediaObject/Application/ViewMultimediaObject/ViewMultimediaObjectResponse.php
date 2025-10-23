<?php

namespace App\MultimediaObject\Application\ViewMultimediaObject;

final class ViewMultimediaObjectResponse
{
    public function __construct(private $object) {}

    public function object()
    {
        return $this->object;
    }
}
