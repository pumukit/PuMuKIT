<?php

namespace Pumukit\SchemaBundle\Document\Traits;

use Pumukit\SchemaBundle\Document\TagInterface;

trait Tag
{
    public function isChildOf(TagInterface $tag): bool
    {
        if ($this->isDescendantOf($tag)) {
            $suffixPath = substr($this->getPath(), strlen($tag->getPath()), strlen($this->getPath()));
            if (1 === substr_count($suffixPath, '|')) {
                return true;
            }
        }

        return false;
    }

    public function isDescendantOf(TagInterface $tag): bool
    {
        if ($tag->getCod() === $this->getCod()) {
            return false;
        }

        return 0 === strpos($this->getPath(), $tag->getPath());
    }

    public function equalsOrDescendantOf(TagInterface $tag): bool
    {
        return 0 === strpos($this->getPath(), $tag->getPath());
    }

    public function isDescendantOfByCod(string $tagCod): bool
    {
        if ($tagCod === $this->getCod()) {
            return false;
        }
        if (0 === strpos($this->getPath(), sprintf('%s|', $tagCod))) {
            return true;
        }

        return false !== strpos($this->getPath(), sprintf('|%s|', $tagCod));
    }

    public function isPubTag(): bool
    {
        return $this->isDescendantOfByCod('PUBCHANNELS') || $this->isDescendantOfByCod('PUBDECISIONS');
    }
}
