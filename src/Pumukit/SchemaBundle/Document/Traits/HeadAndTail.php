<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\Traits;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

trait HeadAndTail
{
    /**
     * @MongoDB\Field(type="string")
     */
    private $videoHead;

    /**
     * @MongoDB\Field(type="string")
     */
    private $videoTail;

    public function getVideoHead(): ?string
    {
        return $this->videoHead;
    }

    public function setVideoHead(?string $videoHead): void
    {
        $this->videoHead = $videoHead;
    }

    public function getVideoTail(): ?string
    {
        return $this->videoTail;
    }

    public function setVideoTail(?string $videoTail): void
    {
        $this->videoTail = $videoTail;
    }
}
