<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Pic;

/**
 * @internal
 * @coversNothing
 */
class PicTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $tags = ['tag_a', 'tag_b'];
        $url = '/mnt/video/123/23435.mp4';
        $path = '/mnt/video/123/23435.mp4';
        $mime = 'image/jpg';

        $size = 3456;
        $width = 800;
        $height = 600;
        $hide = true; // Change assertTrue accordingly.

        $pic = new Pic();

        $pic->setTags($tags);
        $pic->setUrl($url);
        $pic->setPath($path);
        $pic->setMimeType($mime);
        $pic->setSize($size);
        $pic->setWidth($width);
        $pic->setHeight($height);
        $pic->setHide($hide);

        static::assertEquals($tags, $pic->getTags());
        static::assertEquals($url, $pic->getUrl());
        static::assertEquals($path, $pic->getPath());
        static::assertEquals($mime, $pic->getMimeType());
        static::assertEquals($size, $pic->getSize());
        static::assertEquals($width, $pic->getWidth());
        static::assertEquals($height, $pic->getHeight());
        static::assertTrue($pic->getHide());
    }

    public function testTagCollection()
    {
        $pic = new Pic();
        static::assertFalse($pic->containsTag('t'));
        $pic->addTag('t');
        static::assertTrue($pic->containsTag('t'));
        $pic->removeTag('t');
        static::assertFalse($pic->containsTag('t'));

        //Repeat Tag
        static::assertFalse($pic->containsTag('t'));
        $pic->addTag('t');
        $pic->addTag('t');
        static::assertTrue($pic->containsTag('t'));
        $pic->removeTag('t');
        static::assertFalse($pic->containsTag('t'));
        static::assertFalse($pic->removeTag('t'));

        //containsAllTag and containsAnyTag
        $pic->addTag('t1');
        $pic->addTag('t2');
        $pic->addTag('t3');
        static::assertTrue($pic->containsAnyTag(['t0', 't2']));
        static::assertTrue($pic->containsAnyTag(['t2', 't3']));
        static::assertFalse($pic->containsAnyTag(['t0', 't4']));
        static::assertTrue($pic->containsAllTags(['t1', 't2']));
        static::assertTrue($pic->containsAllTags(['t1']));
        static::assertFalse($pic->containsAllTags(['t0', 't2']));
        static::assertFalse($pic->containsAllTags(['t0', 't1', 't2', 't3']));
    }

    /*public function testRef()
    {
        $t1 = new pic();
        $t2 = new pic();

        $t2->setRef($t1);
        $this->assertEquals(null, $t1->getRef());
        $this->assertEquals($t1, $t2->getRef());
    }*/
}
