<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Element;

/**
 * @internal
 * @coversNothing
 */
class ElementTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $tags = ['tag_a', 'tag_b'];
        $locale = 'en';
        $url = '/mnt/video/123/23435.mp4';
        $path = '/mnt/video/123/23435.mp4';
        $mime = 'video/mpeg4';
        $hide = false;
        $description = 'description';
        $localeEs = 'es';

        $element = new Element();
        $element->setTags($tags);
        $element->setLocale($locale);
        $element->setUrl($url);
        $element->setPath($path);
        $element->setMimeType($mime);
        $element->setHide($hide);
        $element->setDescription($description);

        $this->assertEquals($tags, $element->getTags());
        $this->assertEquals($locale, $element->getLocale());
        $this->assertEquals($url, $element->getUrl());
        $this->assertEquals($path, $element->getPath());
        $this->assertEquals($mime, $element->getMimeType());
        $this->assertFalse($hide, $element->getHide());
        $this->assertEquals($description, $element->getDescription());

        $description = null;
        $element->setDescription($description);
        $this->assertEquals($description, $element->getDescription());

        $description = 'description';
        $descriptionEs = 'descripción';
        $descriptionI18n = [$locale => $description, $localeEs => $descriptionEs];
        $element->setI18nDescription($descriptionI18n);
        $this->assertEquals($descriptionI18n, $element->getI18nDescription());
    }

    public function testMaxSize()
    {
        $size = 5368709120; // 5GB, integer types in 32 bits machines only admit 2GB

        $element = new Element();
        $element->setSize($size);
        $this->assertEquals($size, $element->getSize());
    }

    public function testTagCollection()
    {
        $element = new Element();
        $this->assertFalse($element->containsTag('t'));
        $element->addTag('t');
        $this->assertTrue($element->containsTag('t'));
        $element->removeTag('t');
        $this->assertFalse($element->containsTag('t'));

        //Repeat Tag
        $this->assertFalse($element->containsTag('t'));
        $element->addTag('t');
        $element->addTag('t');
        $this->assertTrue($element->containsTag('t'));
        $element->removeTag('t');
        $this->assertFalse($element->containsTag('t'));
        $this->assertFalse($element->removeTag('t'));

        //containsAllTag and containsAnyTag
        $element->addTag('t1');
        $element->addTag('t2');
        $element->addTag('t3');
        $this->assertTrue($element->containsAnyTag(['t0', 't2']));
        $this->assertTrue($element->containsAnyTag(['t2', 't3']));
        $this->assertFalse($element->containsAnyTag(['t0', 't4']));
        $this->assertTrue($element->containsAllTags(['t1', 't2']));
        $this->assertTrue($element->containsAllTags(['t1']));
        $this->assertFalse($element->containsAllTags(['t0', 't2']));
        $this->assertFalse($element->containsAllTags(['t0', 't1', 't2', 't3']));
    }
}
