<?php

namespace Pumukit\SchemaBundle\Entity;

use Gedmo\Tree\Node;
use Gedmo\Mapping\Annotation as Gedmo;
// Review /vendor/gedmo/doctrine-extensions/lib/Gedmo/Tree/Entity/Repository/NestedTreeRepository.php
use Doctrine\ORM\Mapping as ORM;


/**
 * Pumukit\SchemaBundle\Entity\Tag
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="tag")
 *
 * //ORM\Entity(repositoryClass="Pumukit\SchemaBundle\Entity\TagRepository")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
class Tag implements Node
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
     * @var array $multimedia_objects
     *
     * @ORM\ManyToMany(targetEntity="MultimediaObject", mappedBy="tags")
     */
    private $multimedia_objects;


    /**
     * @var string $title
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string $description
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string $slug
     * 
     * @ORM\Column(name="slug", type="string", length=255, nullable=true)
     * REVISAR
     */
    private $slug;

    /**
     * @var string $cod
     * 
     * @ORM\Column(name="cod", type="string", length=255, nullable=true)
     */
    private $cod = 0;

    /**
     * @var boolean $metatag
     * 
     * @ORM\Column(name="metatag", type="boolean", nullable=true)
     */
    private $metatag = false;    

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;

    /**
     * @var integer $lft
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer", nullable=true)
     */
    private $lft;

    /**
     * @var integer $rgt
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer", nullable=true)
     */
    private $rgt;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Tag", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $parent;

    /**
     * @var integer $root
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", nullable=true)
     */
    private $root;

    /**
     * @var integer $level
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    private $level;

    /**
     * @ORM\OneToMany(targetEntity="Tag", mappedBy="parent")
     * //ORM\OrderBy({"left" = "ASC"})
     */
    private $children;

    /**
     * @var datetime $created 
     * review property: Timestampable(on="create")
     * @ORM\Column(name="created", type="datetime", nullable=true)
     */
    private $created;

    /**
     * @var datetime $updated
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;



    public function __construct($title = null)
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        if ($title != null) {
            $this->setTitle($title);
        }
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

    // TO DO set slug function

    public function getSlug()
    {
        return $this->slug;
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
     * Set metatag
     *
     * @param boolean $metatag
     */
    public function setMetatag($metatag)
    {
        $this->metatag = $metatag;
    }

    /**
     * Get metatag
     *
     * @return boolean 
     */
    public function getMetatag()
    {
        return $this->metatag;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setParent( Tag $parent)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getRoot()
    {
        return $this->root;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getLeft()
    {
        return $this->left;
    }

    public function getRight()
    {
        return $this->right;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function getUpdated()
    {
        return $this->updated;
    }
    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }

    public function __toString()
    {
        return $this->getTitle();
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return Tag
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    
        return $this;
    }

    /**
     * Set lft
     *
     * @param integer $lft
     * @return Tag
     */
    public function setLft($lft)
    {
        $this->lft = $lft;
    
        return $this;
    }

    /**
     * Get lft
     *
     * @return integer 
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     * @return Tag
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;
    
        return $this;
    }

    /**
     * Get rgt
     *
     * @return integer 
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set root
     *
     * @param integer $root
     * @return Tag
     */
    public function setRoot($root)
    {
        $this->root = $root;
    
        return $this;
    }

    /**
     * Set level
     *
     * @param integer $level
     * @return Tag
     */
    public function setLevel($level)
    {
        $this->level = $level;
    
        return $this;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Tag
     */
    public function setCreated($created)
    {
        $this->created = $created;
    
        return $this;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return Tag
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    
        return $this;
    }

    /**
     * Add multimedia_objects
     *
     * @param \Pumukit\SchemaBundle\Entity\MultimediaObject $multimediaObjects
     * @return Tag
     */
    public function addMultimediaObject(\Pumukit\SchemaBundle\Entity\MultimediaObject $multimediaObjects)
    {
        $this->multimedia_objects[] = $multimediaObjects;
    
        return $this;
    }

    /**
     * Remove multimedia_objects
     *
     * @param \Pumukit\SchemaBundle\Entity\MultimediaObject $multimediaObjects
     */
    public function removeMultimediaObject(\Pumukit\SchemaBundle\Entity\MultimediaObject $multimediaObjects)
    {
        $this->multimedia_objects->removeElement($multimediaObjects);
    }

    /**
     * Get multimedia_objects
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMultimediaObjects()
    {
        return $this->multimedia_objects;
    }

    /**
     * Add children
     *
     * @param \Pumukit\SchemaBundle\Entity\Tag $children
     * @return Tag
     */
    public function addChildren(\Pumukit\SchemaBundle\Entity\Tag $children)
    {
        $this->children[] = $children;
    
        return $this;
    }

    /**
     * Remove children
     *
     * @param \Pumukit\SchemaBundle\Entity\Tag $children
     */
    public function removeChildren(\Pumukit\SchemaBundle\Entity\Tag $children)
    {
        $this->children->removeElement($children);
    }
}