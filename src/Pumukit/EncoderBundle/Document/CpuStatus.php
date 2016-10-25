<?php

namespace Pumukit\EncoderBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Pumukit\EncoderBundle\Document\CpuStatus
 *
 * @MongoDB\Document(repositoryClass="Pumukit\EncoderBundle\Repository\CpuStatusRepository")
 *
 */
class CpuStatus
{
    const STATUS_MAINTENANCE = -1;
    const STATUS_WORKING = 0;

    /**
     * Status codes translation table.
     *
     * @var array
     */
    public static $statusTexts = array(
        self::STATUS_MAINTENANCE => "In Maintenance",
        self::STATUS_WORKING => "Working",
    );

    /**
     * @var int $id
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var string $name
     *
     * @MongoDB\String
     */
    private $name;

    /**
     * @var int $status
     *
     * @MongoDB\Int
     */
    private $status = self::STATUS_WORKING;

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set status
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
}
