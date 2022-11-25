<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Event;

class FileRemovedEvent extends \Symfony\Contracts\EventDispatcher\Event
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
