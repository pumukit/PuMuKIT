<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class Series
{

    /**
     * @var datetime $public_date
     *
     * @MongoDB\DateTime
     */
    private $public_date;

    /**
     * @var string $title
     *
     * @MongoDB\Text
     */
    private $title;

    /**
     * @var string $subtitle
     *
     * @MongoDB\Text
     */
    private $subtitle;

    /**
     * @var text $description
     *
     * @MongoDB\Text
     */
    private $description;

    /**
     * Set public_date
     *
     * @param datetime $publicDate
     */
    public function setPublicDate($publicDate)
    {
        $this->public_date = $publicDate;
    }

    /**
     * Get public_date
     *
     * @return datetime
     */
    public function getPublicDate()
    {
        return $this->public_date;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set subtitle
     *
     * @param string $subtitle
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
    }

    /**
     * Get subtitle
     *
     * @return string
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * Set description
     *
     * @param text $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get description
     *
     * @return text
     */
    public function getDescription()
    {
        return $this->description;
    }
}