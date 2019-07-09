<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\SchemaBundle\Document\Annotation.
 *
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\AnnotationRepository")
 */
class Annotation
{
    /**
     * @var int
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var string
     *
     * @MongoDB\Field(type="object_id")
     */
    //This field would be the equivalent to 'mediapackage_id' on opencast.
    private $multimediaObject;

    /**
     * @var \DateTime
     *
     * @MongoDB\Field(type="date")
     */
    private $created;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $type;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $user_id;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $session;

    /**
     * @var int
     *
     * @MongoDB\Field(type="int")
     */
    private $inpoint;

    /**
     * @var int
     *
     * @MongoDB\Field(type="int")
     */
    private $outpoint;

    /**
     * @var int
     *
     * @MongoDB\Field(type="int")
     */
    private $length;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $value;

    /**
     * @var bool
     *
     * @MongoDB\Field(type="boolean")
     */
    private $is_private;

    public function __clone()
    {
        $this->id = null;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getValue();
    }

    /**
     * Get id.
     *
     * @return string $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set multimediaObject.
     *
     * @param string $multimediaObject
     *
     * @return self
     */
    public function setMultimediaObject($multimediaObject)
    {
        $this->multimediaObject = $multimediaObject;

        return $this;
    }

    /**
     * Get multimediaObject.
     *
     * @return string $multimediaObject
     */
    public function getMultimediaObject()
    {
        return $this->multimediaObject;
    }

    /**
     * Set created.
     *
     * @param \DateTime $created
     *
     * @return self
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return \DateTime $created
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set userId.
     *
     * @param string $userId
     *
     * @return self
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return string $userId
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set session.
     *
     * @param string $session
     *
     * @return self
     */
    public function setSession($session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Get session.
     *
     * @return string $session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set inpoint.
     *
     * @param int $inpoint
     *
     * @return self
     */
    public function setInpoint($inpoint)
    {
        $this->inpoint = $inpoint;

        return $this;
    }

    /**
     * Get inpoint.
     *
     * @return int $inpoint
     */
    public function getInpoint()
    {
        return $this->inpoint;
    }

    /**
     * Set outpoint.
     *
     * @param int $outpoint
     *
     * @return self
     */
    public function setOutpoint($outpoint)
    {
        $this->outpoint = $outpoint;

        return $this;
    }

    /**
     * Get outpoint.
     *
     * @return int $outpoint
     */
    public function getOutpoint()
    {
        return $this->outpoint;
    }

    /**
     * Set length.
     *
     * @param int $length
     *
     * @return self
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * Get length.
     *
     * @return int $length
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Set value.
     *
     * @param string $value
     *
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return string $value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set private.
     *
     * @param bool $isPrivate
     *
     * @return self
     */
    public function setPrivate($isPrivate)
    {
        $this->is_private = $isPrivate;

        return $this;
    }

    /**
     * is Private.
     *
     * @return bool $isPrivate
     */
    public function isPrivate()
    {
        return $this->is_private;
    }
}
