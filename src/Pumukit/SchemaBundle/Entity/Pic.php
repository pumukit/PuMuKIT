<?php

namespace Pumukit\SchemaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Pumukit\SchemaBundle\Entity\Pic
 *
 * @ORM\Table(name="pic")
 * @ORM\Entity(repositoryClass="Pumukit\SchemaBundle\Entity\PicRepository")
 */
class Pic extends Element
{
    /**
     * @ORM\ManyToOne(targetEntity="Series", inversedBy="id")
     * @ORM\JoinColumn(name="series_id", referencedColumnName="id")
     */
    private $series;

    /**
     * @var integer $width
     *
     * @ORM\Column(name="width", type="integer")
     */
    private $width;

    /**
     * @var integer $height
     *
     * @ORM\Column(name="height", type="integer")
     */
    private $height;

    /**
     * Set series
     *
     * @param Series $series
     */
    public function setSeries(Series $series)
    {
        $this->series = $series;
    }

    /**
     * Get series
     *
     * @return Series
     */
    public function getSeries()
    {
        return $this->series;
    }

    /**
     * Set width
     *
     * @param integer $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * Get width
     *
     * @return integer 
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param integer $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * Get height
     *
     * @return integer 
     */
    public function getHeight()
    {
        return $this->height;
    }
}
