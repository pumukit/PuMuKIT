<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class FileRemovedEvent extends Event
{
    protected $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }
}
