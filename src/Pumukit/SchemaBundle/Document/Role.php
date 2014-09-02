<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\RoleRepository")
 */
class Role
{

    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @var string $cod
     *
     * @MongoDB\String
     */
    private $cod = 0;

    /**
     * @var integer $rank
     *
     * @MongoDB\Int
     */
    private $rank;

    /**
     * See European Broadcasting Union Role Codes
     * @var string $xml
     *
     * @MongoDB\String
     */
    private $xml;

    /**
     * @var boolean $display
     *
     * @MongoDB\Boolean
     */
    private $display = true;

    /**
     * @var string $name
     *
     * @MongoDB\String
     */
    private $name;

    /**
     * @var string $text
     *
     * @MongoDB\String
     */
    private $text;

    /**
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
