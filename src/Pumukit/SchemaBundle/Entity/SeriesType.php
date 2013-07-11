<?php

namespace Pumukit\SchemaBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * Pumukit\SchemaBundle\Entity\SeriesType
 *
 * @ORM\Table(name="series_type")
 * @ORM\Entity(repositoryClass="Pumukit\SchemaBundle\Entity\SeriesTypeRepository")
 */
class SeriesType
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $name
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var text $description
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string $cod
     *
     * @ORM\Column(name="cod", type="string", length=20, nullable=true)
     */
    private $cod = 0;

    /**
     * @var ArrayCollection $series
     *
     * @ORM\OneToMany(targetEntity="Series",  mappedBy="series_type", cascade={"remove"}) 
     */
    private $series;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;

    public function __construct()
    {
        $this->series    = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

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
     * Set description
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set cod
     *
     * @param string $cod
     */
    public function setCod($cod)
    {
        $this->cod = $cod;
    }

    /**
     * Get cod
     *
     * @return string 
     */
    public function getCod()
    {
        return $this->cod;
    }

   /**
     * Add series
     *
     * @param Series $series
     */
    public function addSeries(Series $series)
    {
        $this->series[] = $series;
        // $this->series = array_unique($this->tags);
        // Extra verification
        $series->setSeriesType($this);
    }

    /**
     * Remove series
     *
     * @param Series $series
     */
    public function removeSeries(Series $series)
    {
        $this->series->removeElement($series);
    }

    /**
     * Contains series
     *
     * @param Series $series
     *
     * @return boolean
     */
    public function containsSeries(Series $series)
    {
        return $this->series->contains($series);
    }

    /**
     * Get series
     *
     * @return ArrayCollection
     */
    public function getSeries()
    {
        return $this->series;
    }    

}