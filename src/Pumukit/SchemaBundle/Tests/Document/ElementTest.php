<?php

declare(strict_types=1);

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

        static::assertEquals($tags, $element->getTags());
        static::assertEquals($locale, $element->getLocale());
        static::assertEquals($url, $element->getUrl());
        static::assertEquals($path, $element->getPath());
        static::assertEquals($mime, $element->getMimeType());
        static::assertFalse($hide, $element->getHide());
        static::assertEquals($description, $element->getDescription());

        $description = null;
        $element->setDescription($description);
        static::assertEquals($description, $element->getDescription());

        $description = 'description';
        $descriptionEs = 'descripción';
        $descriptionI18n = [$locale => $description, $localeEs => $descriptionEs];
        $element->setI18nDescription($descriptionI18n);
        static::assertEquals($descriptionI18n, $element->getI18nDescription());
    }

    public function testMaxSize()
    {
        $size = 5368709120; // 5GB, integer types in 32 bits machines only admit 2GB

        $element = new Element();
        $element->setSize($size);
        static::assertEquals($size, $element->getSize());
    }

    public function testTagCollection()
    {
        $element = new Element();
        static::assertFalse($element->containsTag('t'));
        $element->addTag('t');
        static::assertTrue($element->containsTag('t'));
        $element->removeTag('t');
        static::assertFalse($element->containsTag('t'));

        //Repeat Tag
        static::assertFalse($element->containsTag('t'));
        $element->addTag('t');
        $element->addTag('t');
        static::assertTrue($element->containsTag('t'));
        $element->removeTag('t');
        static::assertFalse($element->containsTag('t'));
        static::assertFalse($element->removeTag('t'));

        //containsAllTag and containsAnyTag
        $element->addTag('t1');
        $element->addTag('t2');
        $element->addTag('t3');
        static::assertTrue($element->containsAnyTag(['t0', 't2']));
        static::assertTrue($element->containsAnyTag(['t2', 't3']));
        static::assertFalse($element->containsAnyTag(['t0', 't4']));
        static::assertTrue($element->containsAllTags(['t1', 't2']));
        static::assertTrue($element->containsAllTags(['t1']));
        static::assertFalse($element->containsAllTags(['t0', 't2']));
        static::assertFalse($element->containsAllTags(['t0', 't1', 't2', 't3']));
    }
}
