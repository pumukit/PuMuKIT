<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Document\ValueObject;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;

final class TagsTest extends TestCase
{
    public function testCreate(): void
    {
        $tagsArray = ['tag1', 'tag2'];
        $tags = Tags::create($tagsArray);

        $this->assertInstanceOf(Tags::class, $tags);
        $this->assertEquals($tagsArray, $tags->toArray());
    }

    public function testAdd(): void
    {
        $tags = Tags::create([]);
        $tags->add('tag1');

        $this->assertTrue($tags->contains('tag1'));
    }

    public function testAddUnique(): void
    {
        $tags = Tags::create(['tag1']);
        $tags->add('tag1');

        $this->assertCount(1, $tags->toArray());
    }

    public function testRemove(): void
    {
        $tags = Tags::create(['tag1', 'tag2']);
        $tags->remove('tag1');

        $this->assertFalse($tags->contains('tag1'));
    }

    public function testContains(): void
    {
        $tags = Tags::create(['tag1', 'tag2']);

        $this->assertTrue($tags->contains('tag1'));
        $this->assertFalse($tags->contains('tag3'));
    }

    public function testContainsAllTags(): void
    {
        $tags = Tags::create(['tag1', 'tag2']);

        $this->assertTrue($tags->containsAllTags(['tag1', 'tag2']));
        $this->assertFalse($tags->containsAllTags(['tag2', 'tag3']));
    }

    public function testContainsAnyTag(): void
    {
        $tags = Tags::create(['tag1', 'tag2']);

        $this->assertTrue($tags->containsAnyTag(['tag2', 'tag3']));
        $this->assertFalse($tags->containsAnyTag(['tag3', 'tag4']));
    }
}
