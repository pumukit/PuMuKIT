<?php

namespace Pumukit\SchemaBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * Pumukit\SchemaBundle\Entity\Element
 *
 * @ORM\MappedSuperclass
 */
class Element
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
     * @ORM\ManyToOne(targetEntity="MultimediaObject", inversedBy="id")
     * @ORM\JoinColumn(name="multimedia_object_id", referencedColumnName="id")
     */
    private $multimedia_object;

    /**
     * @ORM\OneToOne(targetEntity="Element")
     * @ORM\JoinColumn(name="ref_id", referencedColumnName="id")
     **/
    private $ref = null;

    /**
     * @var array $tags
     *
     * @ORM\Column(name="tags", type="array", nullable=true)
     */
    private $tags;

    /**
     * @var string $url
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var string $path
     *
     * @ORM\Column(name="path", type="string", length=255, nullable=true)
     */
    private $path;

    /**
     * @var string $format
     *
     * @ORM\Column(name="format", type="string", length=20, nullable=true)
     */
    private $format;

    /**
     * @var string $mime_type
     *
     * @ORM\Column(name="mime_type", type="string", length=20, nullable=true)
     */
    private $mime_type;

    /**
     * @var integer $rank
     *
     * @ORM\Column(name="rank", type="integer")
     */
    private $rank;

    /**
     * @var integer $size
     *
     * @ORM\Column(name="size", type="integer")
     */
    private $size;

    /**
     * @var boolean $hide
     *
     * @ORM\Column(name="hide", type="boolean")
     */
    private $hide = false;

    /**
     * @var text $description
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    public function __construct()
    {
        $this->tags = array();
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
     * Set multimedia_object
     *
     * @param MultimediaObject $multimedia_object
     */
    public function setMultimediaObject(MultimediaObject $multimedia_object)
    {
        $this->multimedia_object = $multimedia_object;
    }

    /**
     * Get multimedia_object
     *
     * @return MultimediaObject
     */
    public function getMultimediaObject()
    {
        return $this->multimedia_object;
    }

    /**
     * Set ref
     *
     * @param Pic $ref
     */
    public function setRef(Element $ref)
    {
        $this->ref = $ref;
    }

    /**
     * Get ref
     *
     * @return Pic
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * Set tags
     *
     * @param array $tags
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * Get tags
     *
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Add tag
     *
     * @param string $tag
     */
    public function addTag($tag)
    {
        $this->tags[] = $tag;

        return $this->tags = array_unique($this->tags);
    }

    /**
     * Remove tag
     *
     * @param  string  $tag
     * @return boolean TRUE if this pic contained the specified tag, FALSE otherwise.
     */
    public function removeTag($tag)
    {
        $tag = array_search($tag, $this->tags, true);

        if ($tag !== false) {
            unset($this->tags[$tag]);

            return true;
        }

        return false;
    }

    /**
     * Contains tag
     *
     * @param  string  $tag
     * @return boolean TRUE if this pic contained the specified tag, FALSE otherwise.
     */
    public function containsTag($tag)
    {
        return in_array($tag, $this->tags, true);
    }

    /**
     * Contains all tags
     *
     * @param  array   $tags
     * @return boolean TRUE if this pic contained all tags, FALSE otherwise.
     */
    public function containsAllTags(array $tags)
    {
        return count(array_intersect($tags, $this->tags)) === count($tags);
    }

    /**
     * Contains any tags
     *
     * @param  array   $tags
     * @return boolean TRUE if this pic contained any tag of the list, FALSE otherwise.
     */
    public function containsAnyTag(array $tags)
    {
        return count(array_intersect($tags, $this->tags)) != 0;
    }

    /**
     * Set url
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set path
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set format
     *
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Get format
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set mime_type
     *
     * @param string $mime_type
     */
    public function setMimeType($mime_type)
    {
        $this->mime_type = $mime_type;
    }

    /**
     * Get mime_type
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mime_type;
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
     * Set size
     *
     * @param integer $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * Get size
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set hide
     *
     * @param boolean $hide
     */
    public function setHide($hide)
    {
        $this->hide = $hide;
    }

    /**
     * Get hide
     *
     * @return boolean
     */
    public function getHide()
    {
        return $this->hide;
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
