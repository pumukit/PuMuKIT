<?php
declare(strict_types=1);
namespace Pumukit\SchemaBundle\Document\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Pumukit\SchemaBundle\Document\Pic as DocumentPic;

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
     * @MongoDB\EmbedMany(targetDocument=Pic::class)
     */
    private $pics;

    public function __construct()
    {
        $this->pics = new ArrayCollection();
    }

    public function addPic(DocumentPic $pic): void
    {
        $this->pics->add($pic);
    }

    public function removePic(DocumentPic $pic): void
    {
        $this->pics->removeElement($pic);
    }

    public function removePicById($picId): void
    {
        $this->pics = $this->pics->filter(function ($pic) use ($picId) {
            return $pic->getId() !== $picId;
        });
    }

    public function upPicById($picId): void
    {
        $this->reorderPicById($picId);
    }

    public function downPicById($picId): void
    {
        $this->reorderPicById($picId, false);
    }

    public function containsPic(DocumentPic $pic): bool
    {
        return $this->pics->contains($pic);
    }

    public function getPics()
    {
        return $this->pics;
    }

    public function getPic()
    {
        return $this->pics->get(0);
    }

    public function getPicById($picId)
    {
        foreach ($this->pics as $pic) {
            if ($pic->getId() === $picId) {
                return $pic;
            }
        }

        return null;
    }

    /**
     * @deprecated: Use PicService, function getFirstUrlPic($object, $absolute, $hd) Get first pic url
     */
    public function getFirstUrlPic(string $default = ''): string
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

    public function getPicsWithTag($tag): array
    {
        $r = [];

        foreach ($this->pics as $pic) {
            if ($pic->containsTag($tag)) {
                $r[] = $pic;
            }
        }

        return $r;
    }

    public function getPicWithTag($tag)
    {
        foreach ($this->pics as $pic) {
            if ($pic->containsTag($tag)) {
                return $pic;
            }
        }

        return null;
    }

    public function getPicsWithAllTags(array $tags): array
    {
        $r = [];

        foreach ($this->pics as $pic) {
            if ($pic->containsAllTags($tags)) {
                $r[] = $pic;
            }
        }

        return $r;
    }

    public function getPicWithAllTags(array $tags)
    {
        foreach ($this->pics as $pic) {
            if ($pic->containsAllTags($tags)) {
                return $pic;
            }
        }

        return null;
    }

    public function getPicsWithAnyTag(array $tags): array
    {
        $r = [];

        foreach ($this->pics as $pic) {
            if ($pic->containsAnyTag($tags)) {
                $r[] = $pic;
            }
        }

        return $r;
    }

    public function getPicWithAnyTag(array $tags)
    {
        foreach ($this->pics as $pic) {
            if ($pic->containsAnyTag($tags)) {
                return $pic;
            }
        }

        return null;
    }

    public function getFilteredPicsWithTags(array $any_tags = [], array $all_tags = [], array $not_any_tags = [], array $not_all_tags = []): array
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

    private function reorderPicById($picId, $up = true): void
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
}
