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

        $this->assertEquals($name, $material->getName());
        $this->assertEquals($tags, $material->getTags());
        $this->assertEquals($url, $material->getUrl());
        $this->assertEquals($path, $material->getPath());
        $this->assertEquals($mime, $material->getMimeType());
        $this->assertFalse($hide, $material->getHide());
        $this->assertEquals($language, $material->getLanguage());

        $name = null;
        $material->setName(null);
        $this->assertEquals($name, $material->getName());
    }

    public function testMaxSize()
    {
        $size = 5368709120; // 5GB, integer types in 32 bits machines only admit 2GB

        $material = new Material();
        $material->setSize($size);
        $this->assertEquals($size, $material->getSize());
    }

    public function testTagCollection()
    {
        $material = new Material();
        $this->assertFalse($material->containsTag('t'));
        $material->addTag('t');
        $this->assertTrue($material->containsTag('t'));
        $material->removeTag('t');
        $this->assertFalse($material->containsTag('t'));

        //Repeat Tag
        $this->assertFalse($material->containsTag('t'));
        $material->addTag('t');
        $material->addTag('t');
        $this->assertTrue($material->containsTag('t'));
        $material->removeTag('t');
        $this->assertFalse($material->containsTag('t'));
        $this->assertFalse($material->removeTag('t'));

        //containsAllTag and containsAnyTag
        $material->addTag('t1');
        $material->addTag('t2');
        $material->addTag('t3');
        $this->assertTrue($material->containsAnyTag(['t0', 't2']));
        $this->assertTrue($material->containsAnyTag(['t2', 't3']));
        $this->assertFalse($material->containsAnyTag(['t0', 't4']));
        $this->assertTrue($material->containsAllTags(['t1', 't2']));
        $this->assertTrue($material->containsAllTags(['t1']));
        $this->assertFalse($material->containsAllTags(['t0', 't2']));
        $this->assertFalse($material->containsAllTags(['t0', 't1', 't2', 't3']));
    }
}
