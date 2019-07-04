<?php

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

        $this->assertEquals($tags, $pic->getTags());
        $this->assertEquals($url, $pic->getUrl());
        $this->assertEquals($path, $pic->getPath());
        $this->assertEquals($mime, $pic->getMimeType());
        $this->assertEquals($size, $pic->getSize());
        $this->assertEquals($width, $pic->getWidth());
        $this->assertEquals($height, $pic->getHeight());
        $this->assertTrue($pic->getHide());
    }

    public function testTagCollection()
    {
        $pic = new Pic();
        $this->assertFalse($pic->containsTag('t'));
        $pic->addTag('t');
        $this->assertTrue($pic->containsTag('t'));
        $pic->removeTag('t');
        $this->assertFalse($pic->containsTag('t'));

        //Repeat Tag
        $this->assertFalse($pic->containsTag('t'));
        $pic->addTag('t');
        $pic->addTag('t');
        $this->assertTrue($pic->containsTag('t'));
        $pic->removeTag('t');
        $this->assertFalse($pic->containsTag('t'));
        $this->assertFalse($pic->removeTag('t'));

        //containsAllTag and containsAnyTag
        $pic->addTag('t1');
        $pic->addTag('t2');
        $pic->addTag('t3');
        $this->assertTrue($pic->containsAnyTag(['t0', 't2']));
        $this->assertTrue($pic->containsAnyTag(['t2', 't3']));
        $this->assertFalse($pic->containsAnyTag(['t0', 't4']));
        $this->assertTrue($pic->containsAllTags(['t1', 't2']));
        $this->assertTrue($pic->containsAllTags(['t1']));
        $this->assertFalse($pic->containsAllTags(['t0', 't2']));
        $this->assertFalse($pic->containsAllTags(['t0', 't1', 't2', 't3']));
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
