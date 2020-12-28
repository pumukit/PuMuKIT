<?php
declare(strict_types=1);
namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument
 */
class EmbeddedSegment
{
    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="int")
     */
    private $index;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $time;

    /**
     * @MongoDB\Field(type="string")
     */
    private $duration;

    /**
     * @MongoDB\Field(type="string")
     */
    private $relevance;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $hit;

    /**
     * @MongoDB\Field(type="string")
     */
    private $text;

    /**
     * @MongoDB\Field(type="string")
     */
    private $preview;

    public function __toString(): string
    {
        return $this->getText() ?? '';
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function setIndex($index): void
    {
        $this->index = $index;
    }

    public function getTime()
    {
        return $this->time;
    }

    public function setTime($time): void
    {
        $this->time = $time;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function setDuration($duration): void
    {
        $this->duration = $duration;
    }

    public function getRelevance()
    {
        return $this->relevance;
    }

    public function setRelevance($relevance): void
    {
        $this->relevance = $relevance;
    }

    public function isHit()
    {
        return $this->hit;
    }

    public function setHit($hit): void
    {
        $this->hit = $hit;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setText($text): void
    {
        $this->text = $text;
    }

    public function getPreview()
    {
        return $this->preview;
    }

    public function setPreview($preview): void
    {
        $this->preview = $preview;
    }
}
