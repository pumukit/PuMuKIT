<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\SchemaBundle\Document\Pic.
 *
 * @MongoDB\EmbeddedDocument
 */
class Pic extends Element
{
    /**
     * @var int
     *
     * @MongoDB\Field(type="int")
     */
    private $width;

    /**
     * @var int
     *
     * @MongoDB\Field(type="int")
     */
    private $height;

    /**
     * Set width.
     *
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * Get width.
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height.
     *
     * @param int $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * Get height.
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Get time from a tag with time_XXX format.
     *
     * @return integer, default 0
     */
    public function getTime()
    {
        $time = 0;

        foreach ($this->getTags() as $tag) {
            if ('time_' == substr($tag, 0, 5)) {
                return (float) (substr($tag, 5));
            }
        }

        return $time;
    }

    /**
     * To string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getUrl();
    }
}
