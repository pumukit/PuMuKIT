<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Material;

/**
 * @internal
 * @coversNothing
 */
class MaterialTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $name = 'name';
        $tags = ['tag_a', 'tag_b'];
        $url = '/mnt/video/123/23435.mp4';
        $path = '/mnt/video/123/23435.mp4';
        $mime = 'video/mpeg4';
        $hide = false;
        $language = 'en';

        $material = new Material();

        $material->setName($name);
        $material->setTags($tags);
        $material->setUrl($url);
        $material->setPath($path);
        $material->setMimeType($mime);
        $material->setHide($hide);
        $material->setLanguage($language);

        static::assertEquals($name, $material->getName());
        static::assertEquals($tags, $material->getTags());
        static::assertEquals($url, $material->getUrl());
        static::assertEquals($path, $material->getPath());
        static::assertEquals($mime, $material->getMimeType());
        static::assertFalse($hide, $material->getHide());
        static::assertEquals($language, $material->getLanguage());

        $name = null;
        $material->setName(null);
        static::assertEquals($name, $material->getName());
    }

    public function testMaxSize()
    {
        $size = 5368709120; // 5GB, integer types in 32 bits machines only admit 2GB

        $material = new Material();
        $material->setSize($size);
        static::assertEquals($size, $material->getSize());
    }

    public function testTagCollection()
    {
        $material = new Material();
        static::assertFalse($material->containsTag('t'));
        $material->addTag('t');
        static::assertTrue($material->containsTag('t'));
        $material->removeTag('t');
        static::assertFalse($material->containsTag('t'));

        //Repeat Tag
        static::assertFalse($material->containsTag('t'));
        $material->addTag('t');
        $material->addTag('t');
        static::assertTrue($material->containsTag('t'));
        $material->removeTag('t');
        static::assertFalse($material->containsTag('t'));
        static::assertFalse($material->removeTag('t'));

        //containsAllTag and containsAnyTag
        $material->addTag('t1');
        $material->addTag('t2');
        $material->addTag('t3');
        static::assertTrue($material->containsAnyTag(['t0', 't2']));
        static::assertTrue($material->containsAnyTag(['t2', 't3']));
        static::assertFalse($material->containsAnyTag(['t0', 't4']));
        static::assertTrue($material->containsAllTags(['t1', 't2']));
        static::assertTrue($material->containsAllTags(['t1']));
        static::assertFalse($material->containsAllTags(['t0', 't2']));
        static::assertFalse($material->containsAllTags(['t0', 't1', 't2', 't3']));
    }
}
