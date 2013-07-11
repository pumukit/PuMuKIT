<?php

namespace Pumukit\SchemaBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * Pumukit\SchemaBundle\Entity\Role
 *
 * @ORM\Table(name="role")
 * @ORM\Entity(repositoryClass="Pumukit\SchemaBundle\Entity\RoleRepository")
 */
class Role
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
     * @var string $cod
     *
     * @ORM\Column(name="cod", type="string", length=20, unique=true)
     */
    private $cod = 0;

    /**
     * @var integer $rank
     *
     * @ORM\Column(name="rank", type="integer", unique=true)
     */
    private $rank;

    /**
     * See European Broadcasting Union Role Codes
     * @var string $xml
     *
     * @ORM\Column(name="xml", type="string", length=255)
     */
    private $xml;

    /**
     * @var boolean $display
     *
     * @ORM\Column(name="display", type="boolean")
     */
    private $display = true;

    /**
     * @var string $name
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string $text
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="text", type="text")
     */
    private $text;

    /**
     * @var ArrayCollection $person_in_multimedia_object
     *
     * @ORM\OneToMany(targetEntity="PersonInMultimediaObject", mappedBy="role", cascade={"remove"})
     */
    private $people_in_multimedia_object;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;


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
     * Set rank
     *
     * @param integer $rank
     */
    public function setRank($rank)
    {
        $this->rank = $rank;
    }

    /**
     * Get rank
     *
     * @return integer 
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Set xml
     *
     * @param string $xml
     */
    public function setXml($xml)
    {
        $this->xml = $xml;
    }

    /**
     * Get xml
     *
     * @return string 
     */
    public function getXml()
    {
        return $this->xml;
    }

    /**
     * Set display
     *
     * @param boolean $display
     */
    public function setDisplay($display)
    {
        $this->display = $display;
    }

    /**
     * Get display
     *
     * @return boolean 
     */
    public function getDisplay()
    {
        return $this->display;
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
     * Set text
     *
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * Get text
     *
     * @return string 
     */
    public function getText()
    {
        return $this->text;
    }
}