<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\SchemaBundle\Document\EmbeddedEventSession.
 *
 * @MongoDB\EmbeddedDocument
 */
class EmbeddedEventSession
{
    /**
     * @var int
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var date
     *
     * @MongoDB\Date
     */
    private $start;

    /**
     * @var date
     *
     * @MongoDB\Date
     */
    private $ends;

    /**
     * @var int
     *
     * @MongoDB\Int
     */
    private $duration = 0;

    /**
     * @var string
     *
     * @MongoDB\String
     */
    private $notes;

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
     * @return date
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param date $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * @return date
     */
    public function getEnds()
    {
        return $this->ends;
    }

    /**
     * @param date $ends
     */
    public function setEnds($ends)
    {
        $this->ends = $ends;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }
}
