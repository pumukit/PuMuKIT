<?php

namespace Pumukit\SchemaBundle\Document\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Pumukit\SchemaBundle\Document\Pic as DocumentPic;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

trait Pic
{
    /*
        Warning - To use trait PIC you must do:

        use Traits\Pic {
            Traits\Pic::__construct as private __PicConstruct;
        }

        and on class __construct():
        public function __construct()
        {
            ...
            $this->__PicConstruct();
            ...
        }
    */

    /**
     * @var ArrayCollection
     * @MongoDB\EmbedMany(targetDocument="Pic")
     */
    private $pics;

    public function __construct()
    {
        $this->pics = new ArrayCollection();
    }

    /**
     * Add pic.
     *
     * @param DocumentPic $pic
     */
    public function addPic(DocumentPic $pic)
    {
        $this->pics->add($pic);
    }

    /**
     * Remove pic.
     *
     * @param DocumentPic $pic
     */
    public function removePic(DocumentPic $pic)
    {
        $this->pics->removeElement($pic);
    }

    /**
     * Remove pic by id.
     *
     * @param string $picId
     */
    public function removePicById($picId)
    {
        $this->pics = $this->pics->filter(function ($pic) use ($picId) {
            return $pic->getId() !== $picId;
        });
    }

    /**
     * Up pic by id.
     *
     * @param string $picId
     */
    public function upPicById($picId)
    {
        $this->reorderPicById($picId, true);
    }

    /**
     * Down pic by id.
     *
     * @param string $picId
     */
    public function downPicById($picId)
    {
        $this->reorderPicById($picId, false);
    }

    /**
     * Reorder pic by id.
     *
     * @param string $picId
     * @param bool   $up
     */
    private function reorderPicById($picId, $up = true)
    {
        $snapshot = array_values($this->pics->toArray());
        $this->pics->clear();

        $out = [];
        foreach ($snapshot as $key => $pic) {
            if ($pic->getId() === $picId) {
                $out[($key * 10) + ($up ? -11 : 11)] = $pic;
            } else {
                $out[$key * 10] = $pic;
            }
        }

        ksort($out);
        foreach ($out as $pic) {
            $this->pics->add($pic);
        }
    }

    /**
     * Contains pic.
     *
     * @param DocumentPic $pic
     *
     * @return bool
     */
    public function containsPic(DocumentPic $pic)
    {
        return $this->pics->contains($pic);
    }

    /**
     * Get pics.
     *
     * @return ArrayCollection
     */
    public function getPics()
    {
        return $this->pics;
    }

    /**
     * Get first pic, null if none.
     *
     * @return DocumentPic
     */
    public function getPic()
    {
        return $this->pics->get(0);
    }

    /**
     * Get pic by id.
     *
     * @param $picId
     *
     * @return DocumentPic|null
     */
    public function getPicById($picId)
    {
        foreach ($this->pics as $pic) {
            if ($pic->getId() == $picId) {
                return $pic;
            }
        }

        return null;
    }

    /**
     * @deprecated: Use PicService, function getFirstUrlPic($object, $absolute, $hd)
     * Get first pic url
     *
     * @param $default string url returned if series without pics
     *
     * @return string
     */
    public function getFirstUrlPic($default = '')
    {
        $url = $default;
        foreach ($this->pics as $pic) {
            if (null !== $pic->getUrl()) {
                $url = $pic->getUrl();
                break;
            }
        }

        return $url;
    }

    /**
     * Get pics with tag.
     *
     * @param string $tag
     *
     * @return array
     */
    public function getPicsWithTag($tag)
    {
        $r = [];

        foreach ($this->pics as $pic) {
            if ($pic->containsTag($tag)) {
                $r[] = $pic;
            }
        }

        return $r;
    }

    /**
     * Get pic with tag.
     *
     * @param string $tag
     *
     * @return DocumentPic|null
     */
    public function getPicWithTag($tag)
    {
        foreach ($this->pics as $pic) {
            if ($pic->containsTag($tag)) {
                return $pic;
            }
        }

        return null;
    }

    /**
     * Get pics with all tags.
     *
     * @param array $tags
     *
     * @return array
     */
    public function getPicsWithAllTags(array $tags)
    {
        $r = [];

        foreach ($this->pics as $pic) {
            if ($pic->containsAllTags($tags)) {
                $r[] = $pic;
            }
        }

        return $r;
    }

    /**
     * Get pics with all tags.
     *
     * @param array $tags
     *
     * @return DocumentPic|null
     */
    public function getPicWithAllTags(array $tags)
    {
        foreach ($this->pics as $pic) {
            if ($pic->containsAllTags($tags)) {
                return $pic;
            }
        }

        return null;
    }

    /**
     * Get pics with any tag.
     *
     * @param array $tags
     *
     * @return array
     */
    public function getPicsWithAnyTag(array $tags)
    {
        $r = [];

        foreach ($this->pics as $pic) {
            if ($pic->containsAnyTag($tags)) {
                $r[] = $pic;
            }
        }

        return $r;
    }

    /**
     * Get pic with any tag.
     *
     * @param array $tags
     *
     * @return DocumentPic|null
     */
    public function getPicWithAnyTag(array $tags)
    {
        foreach ($this->pics as $pic) {
            if ($pic->containsAnyTag($tags)) {
                return $pic;
            }
        }

        return null;
    }

    /**
     * Get filter pics with tag.
     *
     * @param array $any_tags
     * @param array $all_tags
     * @param array $not_any_tags
     * @param array $not_all_tags
     *
     * @return array
     */
    public function getFilteredPicsWithTags(array $any_tags = [], array $all_tags = [], array $not_any_tags = [], array $not_all_tags = [])
    {
        $r = [];

        foreach ($this->pics as $pic) {
            if ($any_tags && !$pic->containsAnyTag($any_tags)) {
                continue;
            }
            if ($all_tags && !$pic->containsAllTags($all_tags)) {
                continue;
            }
            if ($not_any_tags && $pic->containsAnyTag($not_any_tags)) {
                continue;
            }
            if ($not_all_tags && $pic->containsAllTags($not_all_tags)) {
                continue;
            }

            $r[] = $pic;
        }

        return $r;
    }
}
