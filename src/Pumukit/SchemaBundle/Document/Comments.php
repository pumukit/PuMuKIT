<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument
 */
class Comments
{
    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="date")
     */
    private $date;

    /**
     * @MongoDB\Field(type="string")
     */
    private $text;

    /**
     * @MongoDB\Field(type="int")
     * @MongoDB\EmbedOne(targetDocument=MultimediaObject::class)
     */
    private $multimedia_object_id;

    public function getId()
    {
        return $this->id;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setMultimediaObjectId($multimediaObjectId): self
    {
        $this->multimedia_object_id = $multimediaObjectId;

        return $this;
    }

    public function getMultimediaObjectId()
    {
        return $this->multimedia_object_id;
    }
}
