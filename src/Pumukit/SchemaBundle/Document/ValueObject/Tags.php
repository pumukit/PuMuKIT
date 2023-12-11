<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\ValueObject;

final class Tags
{
    private $tags;

    private function __construct(array $tags)
    {
        $this->tags = $tags;
    }

    public static function create(array $tags): Tags
    {
        return new self($tags);
    }

    public function add(string $tag): void
    {
        $this->tags[] = $tag;
        $this->tags = array_unique($this->tags);
    }

    public function remove(string $tag): void
    {
        $tag = array_search($tag, $this->tags, true);
        if (!$tag) {
            unset($this->tags[$tag]);
        }
    }

    public function contains(string $tag): bool
    {
        return in_array($tag, $this->tags, true);
    }

    public function containsAllTags(array $tags): bool
    {
        return count(array_intersect($tags, $this->tags)) === count($tags);
    }

    public function containsAnyTag(array $tags): bool
    {
        return 0 !== count(array_intersect($tags, $this->tags));
    }
}
