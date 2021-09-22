<?php

declare(strict_types=1);

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
     * @MongoDB\EmbedMany(targetDocument=Link::class)
     */
    private $links;

    public function __construct()
    {
        $this->links = new ArrayCollection();
    }

    public function getLinks()
    {
        return $this->links;
    }

    public function addLink(DocumentLink $link): void
    {
        $this->links->add($link);
    }

    public function removeLink(DocumentLink $link): void
    {
        $this->links->removeElement($link);
    }

    public function removeLinkById($linkId): void
    {
        $this->links = $this->links->filter(function (DocumentLink $link) use ($linkId) {
            return $link->getId() !== $linkId;
        });
    }

    public function upLinkById($linkId): void
    {
        $this->reorderLinkById($linkId);
    }

    public function downLinkById($linkId): void
    {
        $this->reorderLinkById($linkId, false);
    }

    public function containsLink(DocumentLink $link): bool
    {
        return $this->links->contains($link);
    }

    public function getLinkById($linkId)
    {
        foreach ($this->links as $link) {
            if ($link->getId() === $linkId) {
                return $link;
            }
        }

        return null;
    }

    public function getLinksWithTag($tag): array
    {
        $r = [];

        foreach ($this->links as $link) {
            if ($link->containsTag($tag)) {
                $r[] = $link;
            }
        }

        return $r;
    }

    public function getLinkWithTag($tag)
    {
        foreach ($this->links as $link) {
            if ($link->containsTag($tag)) {
                return $link;
            }
        }

        return null;
    }

    public function getLinksWithAllTags(array $tags): array
    {
        $r = [];

        foreach ($this->links as $link) {
            if ($link->containsAllTags($tags)) {
                $r[] = $link;
            }
        }

        return $r;
    }

    public function getLinkWithAllTags(array $tags)
    {
        foreach ($this->links as $link) {
            if ($link->containsAllTags($tags)) {
                return $link;
            }
        }

        return null;
    }

    public function getLinksWithAnyTag(array $tags): array
    {
        $r = [];

        foreach ($this->links as $link) {
            if ($link->containsAnyTag($tags)) {
                $r[] = $link;
            }
        }

        return $r;
    }

    public function getLinkWithAnyTag(array $tags)
    {
        foreach ($this->links as $link) {
            if ($link->containsAnyTag($tags)) {
                return $link;
            }
        }

        return null;
    }

    public function getFilteredLinksWithTags(array $any_tags = [], array $all_tags = [], array $not_any_tags = [], array $not_all_tags = []): array
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

    private function reorderLinkById($linkId, $up = true): void
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
