<?php

namespace Pumukit\SchemaBundle\Document\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Pumukit\SchemaBundle\Document\Link as DocumentLink;

trait Link
{
    /*
        Warning - To use trait MATERIAL you must do:

        use Traits\Link {
            Traits\Link::__construct as private __LinkConstruct;
        }

        and on class __construct():
        public function __construct()
        {
            ...
            $this->__LinkConstruct();
            ...
        }
    */

    /**
     * @var ArrayCollection
     * @MongoDB\EmbedMany(targetDocument="Link")
     */
    private $links;

    public function __construct()
    {
        $this->links = new ArrayCollection();
    }

    /**
     * Get links.
     *
     * @return ArrayCollection
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Add link.
     *
     * @param DocumentLink $link
     */
    public function addLink(DocumentLink $link)
    {
        $this->links->add($link);
    }

    /**
     * Remove link.
     *
     * @param DocumentLink $link
     */
    public function removeLink(DocumentLink $link)
    {
        $this->links->removeElement($link);
    }

    /**
     * Remove link by id.
     *
     * @param string $linkId
     */
    public function removeLinkById($linkId)
    {
        $this->links = $this->links->filter(function ($link) use ($linkId) {
            return $link->getId() !== $linkId;
        });
    }

    /**
     * Up link by id.
     *
     * @param string $linkId
     */
    public function upLinkById($linkId)
    {
        $this->reorderLinkById($linkId, true);
    }

    /**
     * Down link by id.
     *
     * @param string $linkId
     */
    public function downLinkById($linkId)
    {
        $this->reorderLinkById($linkId, false);
    }

    /**
     * Contains link.
     *
     * @param DocumentLink $link
     *
     * @return bool
     */
    public function containsLink(DocumentLink $link)
    {
        return $this->links->contains($link);
    }

    /**
     * Get link by id.
     *
     * @param \MongoId|string $linkId
     *
     * @return null|DocumentLink
     */
    public function getLinkById($linkId)
    {
        foreach ($this->links as $link) {
            if ($link->getId() == $linkId) {
                return $link;
            }
        }

        return null;
    }

    /**
     * Get links with tag.
     *
     * @param string $tag
     *
     * @return array
     */
    public function getLinksWithTag($tag)
    {
        $r = [];

        foreach ($this->links as $link) {
            if ($link->containsTag($tag)) {
                $r[] = $link;
            }
        }

        return $r;
    }

    /**
     * Get link with tag.
     *
     * @param string $tag
     *
     * @return null|DocumentLink
     */
    public function getLinkWithTag($tag)
    {
        foreach ($this->links as $link) {
            if ($link->containsTag($tag)) {
                return $link;
            }
        }

        return null;
    }

    /**
     * Get links with all tags.
     *
     * @param array $tags
     *
     * @return array
     */
    public function getLinksWithAllTags(array $tags)
    {
        $r = [];

        foreach ($this->links as $link) {
            if ($link->containsAllTags($tags)) {
                $r[] = $link;
            }
        }

        return $r;
    }

    /**
     * Get links with all tags.
     *
     * @param array $tags
     *
     * @return null|DocumentLink
     */
    public function getLinkWithAllTags(array $tags)
    {
        foreach ($this->links as $link) {
            if ($link->containsAllTags($tags)) {
                return $link;
            }
        }

        return null;
    }

    /**
     * Get links with any tag.
     *
     * @param array $tags
     *
     * @return array
     */
    public function getLinksWithAnyTag(array $tags)
    {
        $r = [];

        foreach ($this->links as $link) {
            if ($link->containsAnyTag($tags)) {
                $r[] = $link;
            }
        }

        return $r;
    }

    /**
     * Get link with any tag.
     *
     * @param array $tags
     *
     * @return null|DocumentLink
     */
    public function getLinkWithAnyTag(array $tags)
    {
        foreach ($this->links as $link) {
            if ($link->containsAnyTag($tags)) {
                return $link;
            }
        }

        return null;
    }

    /**
     * Get filtered links with tags.
     *
     * @param array $any_tags
     * @param array $all_tags
     * @param array $not_any_tags
     * @param array $not_all_tags
     *
     * @return array
     */
    public function getFilteredLinksWithTags(array $any_tags = [], array $all_tags = [], array $not_any_tags = [], array $not_all_tags = [])
    {
        $r = [];

        foreach ($this->links as $link) {
            if ($any_tags && !$link->containsAnyTag($any_tags)) {
                continue;
            }
            if ($all_tags && !$link->containsAllTags($all_tags)) {
                continue;
            }
            if ($not_any_tags && $link->containsAnyTag($not_any_tags)) {
                continue;
            }
            if ($not_all_tags && $link->containsAllTags($not_all_tags)) {
                continue;
            }

            $r[] = $link;
        }

        return $r;
    }

    /**
     * Reorder link by id.
     *
     * @param string $linkId
     * @param bool   $up
     */
    private function reorderLinkById($linkId, $up = true)
    {
        $snapshot = array_values($this->links->toArray());
        $this->links->clear();

        $out = [];
        foreach ($snapshot as $key => $link) {
            if ($link->getId() === $linkId) {
                $out[($key * 10) + ($up ? -11 : 11)] = $link;
            } else {
                $out[$key * 10] = $link;
            }
        }

        ksort($out);
        foreach ($out as $link) {
            $this->links->add($link);
        }
    }
}
