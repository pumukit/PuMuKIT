<?php

namespace Pumukit\SchemaBundle\Tests\Entity;

use Pumukit\SchemaBundle\Entity\MultimediaObject;
use Pumukit\SchemaBundle\Entity\Series;
use Pumukit\SchemaBundle\Entity\Pic;

class PicTest extends \PHPUnit_Framework_TestCase
{

    public function testGetterAndSetter()
    {
        $mm     = new MultimediaObject();
        $serie  = new Series();
        $tags   = array('tag_a', 'tag_b');
        $url    = '/mnt/video/123/23435.mp4';
        $path   = '/mnt/video/123/23435.mp4';
        $mime   = 'image/jpg';

        $rank   = 123;
        $size   = 3456;
        $width  = 800;
        $height = 600;
        $hide   = true; // Change assertTrue accordingly.

        $pic    = new pic();
        $pic->setMultimediaObject($mm);
        $pic->setSeries($serie);

        $pic->setTags($tags);
        $pic->setUrl($url);
        $pic->setPath($path);
        $pic->setMimeType($mime);
        $pic->setRank($rank);
        $pic->setSize($size);
        $pic->setWidth($width);
        $pic->setHeight($height);
        $pic->setHide($hide);

        $this->assertEquals($mm, $pic->getMultimediaObject());
        $this->assertEquals($serie, $pic->getSeries());
        $this->assertEquals($tags, $pic->getTags());
        $this->assertEquals($url, $pic->getUrl());
        $this->assertEquals($path, $pic->getPath());
        $this->assertEquals($mime, $pic->getMimeType());
        $this->assertEquals($rank, $pic->getRank());
        $this->assertEquals($size, $pic->getSize());
        $this->assertEquals($width, $pic->getWidth());
        $this->assertEquals($height, $pic->getHeight());
        $this->assertTrue ($pic->getHide());

    }

    public function testTagCollection()
    {
        $pic = new pic();
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
        $this->assertTrue($pic->containsAnyTag(array('t0', 't2')));
        $this->assertTrue($pic->containsAnyTag(array('t2', 't3')));
        $this->assertFalse($pic->containsAnyTag(array('t0', 't4')));
        $this->assertTrue($pic->containsAllTags(array('t1', 't2')));
        $this->assertTrue($pic->containsAllTags(array('t1')));
        $this->assertFalse($pic->containsAllTags(array('t0', 't2')));
        $this->assertFalse($pic->containsAllTags(array('t0', 't1', 't2', 't3')));
    }

    public function testRef()
    {
        $t1 = new pic();
        $t2 = new pic();

        $t2->setRef($t1);
        $this->assertEquals(null, $t1->getRef());
        $this->assertEquals($t1, $t2->getRef());
    }

}
