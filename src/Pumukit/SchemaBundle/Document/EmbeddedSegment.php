<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\SchemaBundle\Document\EmbeddedSegment.
 *
 * @MongoDB\EmbeddedDocument
 */
class EmbeddedSegment
{
    /**
     * @var int
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var int
     * @MongoDB\Field(type="int")
     */
    private $index;

    /**
     * @var string
     * @MongoDB\Field(type="raw")
     */
    private $time;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $duration;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $relevance;

    /**
     * @var bool
     * @MongoDB\Field(type="boolean")
     */
    private $hit;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $text;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $preview;

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getText();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param int $index
     */
    public function setIndex($index)
    {
        $this->index = $index;
    }

    /**
     * @return string
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param string $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * @return string
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param string $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return string
     */
    public function getRelevance()
    {
        return $this->relevance;
    }

    /**
     * @param string $relevance
     */
    public function setRelevance($relevance)
    {
        $this->relevance = $relevance;
    }

    /**
     * @return bool
     */
    public function isHit()
    {
        return $this->hit;
    }

    /**
     * @param bool $hit
     */
    public function setHit($hit)
    {
        $this->hit = $hit;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getPreview()
    {
        return $this->preview;
    }

    /**
     * @param string $preview
     */
    public function setPreview($preview)
    {
        $this->preview = $preview;
    }
}
