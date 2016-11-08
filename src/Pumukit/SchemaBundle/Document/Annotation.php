<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\SchemaBundle\Document\Annotation
 *
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\AnnotationRepository")
 */
class Annotation
{
    /**
     * @var int $id
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var object_id $multimediaObject
     *
     * @MongoDB\ObjectId
     */
    //This field would be the equivalent to 'mediapackage_id' on opencast.
    private $multimediaObject;

    /**
     * @var date $created
     *
     * @MongoDB\Date
     */
    private $created;

    /**
     * @var string $type
     *
     * @MongoDB\String
     */
    private $type;

    /**
     * @var string $user_id
     *
     * @MongoDB\String
     */
    private $user_id;

    /**
     * @var string $session
     *
     * @MongoDB\String
     */
    private $session;

    /**
     * @var int $inpoint
     *
     * @MongoDB\Int
     */
    private $inpoint;
    
    /**
     * @var int $outpoint
     *
     * @MongoDB\Int
     */
    private $outpoint;

    /**
     * @var int $outpoint
     *
     * @MongoDB\Int
     */
    private $length;

    /**
     * @var string $outpoint
     *
     * @MongoDB\String
     */
    private $value;

    /**
     * @var boolean $is_private
     *
     * @MongoDB\Boolean
     */
    private $is_private;

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set multimediaObject
     *
     * @param object_id $multimediaObject
     * @return self
     */
    public function setMultimediaObject($multimediaObject)
    {
        $this->multimediaObject = $multimediaObject;

        return $this;
    }

    /**
     * Get multimediaObject
     *
     * @return object_id $multimediaObject
     */
    public function getMultimediaObject()
    {
        return $this->multimediaObject;
    }

    /**
     * Set created
     *
     * @param date $created
     * @return self
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return date $created
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set mediapackage
     *
     * @param string $mediapackage
     * @return self
     */
    public function setMediapackage($mediapackage)
    {
        $this->mediapackage = $mediapackage;

        return $this;
    }

    /**
     * Get mediapackage
     *
     * @return string $mediapackage
     */
    public function getMediapackage()
    {
        return $this->mediapackage;
    }

    /**
     * Set userId
     *
     * @param string $userId
     * @return self
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return string $userId
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set session
     *
     * @param string $session
     * @return self
     */
    public function setSession($session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Get session
     *
     * @return string $session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set inpoint
     *
     * @param int $inpoint
     * @return self
     */
    public function setInpoint($inpoint)
    {
        $this->inpoint = $inpoint;

        return $this;
    }

    /**
     * Get inpoint
     *
     * @return int $inpoint
     */
    public function getInpoint()
    {
        return $this->inpoint;
    }

    /**
     * Set outpoint
     *
     * @param int $outpoint
     * @return self
     */
    public function setOutpoint($outpoint)
    {
        $this->outpoint = $outpoint;

        return $this;
    }

    /**
     * Get outpoint
     *
     * @return int $outpoint
     */
    public function getOutpoint()
    {
        return $this->outpoint;
    }

    /**
     * Set length
     *
     * @param int $length
     * @return self
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * Get length
     *
     * @return int $length
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string $value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set isPrivate
     *
     * @param boolean $isPrivate
     * @return self
     */
    public function setIsPrivate($isPrivate)
    {
        $this->is_private = $isPrivate;

        return $this;
    }

    /**
     * Get isPrivate
     *
     * @return boolean $isPrivate
     */
    public function getIsPrivate()
    {
        return $this->is_private;
    }

    public function __clone()
    {
        $this->id = null;
    }
}
