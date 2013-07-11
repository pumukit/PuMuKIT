<?php

namespace Pumukit\SchemaBundle\Tests\Entity;

use Pumukit\SchemaBundle\Entity\MultimediaObject;
use Pumukit\SchemaBundle\Entity\Track;

class TrackTest extends \PHPUnit_Framework_TestCase
{

    public function testGetterAndSetter()
    {
        $mm         = new MultimediaObject();
        $tags       = array('tag_a', 'tag_b');
        $language   = 'portuñol';
        $url        = '/mnt/video/123/23435.mp4';
        $path       = '/mnt/video/123/23435.mp4';
        $mime       = 'video/mpeg4';
        $duration   = 3456;
        $acodec     = 'aac';
        $vcodec     = 'mpeg4-HP';
        $bitrate    = 10000;
        $framerate  = 25;
        $only_audio = FALSE;
        $rank       = 123;
        $duration   = 66666;
        $width      = 1920;
        $height     = 1080;
        $hide       = FALSE;


        $track    = new Track();
        $track->setMultimediaObject($mm);
        $track->setTags($tags);
        $track->setLanguage($language);
        $track->setUrl($url);
        $track->setPath($path);
        $track->setMimeType($mime);
        $track->setDuration($duration);
        $track->setAcodec($acodec);
        $track->setVcodec($vcodec);
        $track->setBitrate($bitrate);
        $track->setFramerate($framerate);
        $track->setOnlyAudio($only_audio);
        $track->setRank($rank);
        $track->setDuration($duration);
        $track->setWidth($width);
        $track->setHeight($height);
        $track->setHide($hide);

        
        $this->assertEquals($mm, $track->getMultimediaObject());
        $this->assertEquals($tags, $track->getTags());
        $this->assertEquals($language, $track->getLanguage());
        $this->assertEquals($url, $track->getUrl());
        $this->assertEquals($path, $track->getPath());
        $this->assertEquals($mime, $track->getMimeType());
        $this->assertEquals($duration, $track->getDuration());
        $this->assertEquals($acodec, $track->getAcodec());
        $this->assertEquals($vcodec, $track->getVcodec());
        $this->assertEquals($bitrate, $track->getBitrate());
        $this->assertEquals($framerate, $track->getFramerate());
        $this->assertFalse($only_audio, $track->getOnlyAudio());
        $this->assertEquals($rank, $track->getRank());
        $this->assertEquals($duration, $track->getDuration());
        $this->assertEquals($width, $track->getWidth());
        $this->assertEquals($height, $track->getHeight());
        $this->assertFalse($hide, $track->getHide());
    }

    public function testMaxSize()
    {
        $size      = 5368709120; // 5GB, integer types in 32 bits machines only admit 2GB
        
        $track    = new Track();
        $track->setSize($size);
        $this->assertEquals($size,$track->getSize());
    }


    public function testTagCollection()
    {
        $track = new Track();
        $this->assertFalse($track->containsTag('t'));
        $track->addTag('t');
        $this->assertTrue($track->containsTag('t'));
        $track->removeTag('t');
        $this->assertFalse($track->containsTag('t'));
        
        //Repeat Tag
        $this->assertFalse($track->containsTag('t'));
        $track->addTag('t');
        $track->addTag('t');
        $this->assertTrue($track->containsTag('t'));
        $track->removeTag('t');
        $this->assertFalse($track->containsTag('t'));
        $this->assertFalse($track->removeTag('t'));
        
        //containsAllTag and containsAnyTag
        $track->addTag('t1');
        $track->addTag('t2');
        $track->addTag('t3');
        $this->assertTrue($track->containsAnyTag(array('t0', 't2')));
        $this->assertTrue($track->containsAnyTag(array('t2', 't3')));
        $this->assertFalse($track->containsAnyTag(array('t0', 't4')));
        $this->assertTrue($track->containsAllTags(array('t1', 't2')));
        $this->assertTrue($track->containsAllTags(array('t1')));
        $this->assertFalse($track->containsAllTags(array('t0', 't2')));
        $this->assertFalse($track->containsAllTags(array('t0', 't1', 't2', 't3')));
    }


    public function testRef()
    {
        $t1 = new Track();
        $t2 = new Track();

        $t2->setRef($t1);
        $this->assertEquals(null, $t1->getRef());
        $this->assertEquals($t1, $t2->getRef());	
    }

}