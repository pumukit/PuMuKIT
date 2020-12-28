<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument
 */
class Pic extends Element
{
    /**
     * @MongoDB\Field(type="int")
     */
    private $width;

    /**
     * @MongoDB\Field(type="int")
     */
    private $height;

    public function __toString(): string
    {
        return $this->getUrl() ?? '';
    }

    public function setWidth($width): void
    {
        $this->width = $width;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function setHeight($height): void
    {
        $this->height = $height;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getTime()
    {
        $time = 0;

        foreach ($this->getTags() as $tag) {
            if (0 === strpos($tag, 'time_')) {
                return (float) (substr($tag, 5));
            }
        }

        return $time;
    }
}
