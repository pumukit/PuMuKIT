<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Track;
use PHPUnit\Framework\TestCase;

class TrackTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $tags = ['tag_a', 'tag_b'];
        $language = 'portuñol';
        $url = '/mnt/video/123/23435.mp4';
        $path = '/mnt/video/123/23435.mp4';
        $mime = 'video/mpeg4';
        $duration = 3456;
        $acodec = 'aac';
        $vcodec = 'mpeg4-HP';
        $bitrate = 10000;
        $framerate = '25/1';
        $only_audio = false;
        $channels = 1;
        $duration = 66666;
        $width = 1920;
        $height = 1080;
        $hide = false;
        $numview = 3;
        $resolution = ['width' => $width, 'height' => $height];

        $track = new Track();
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
        $track->setChannels($channels);
        $track->setDuration($duration);
        $track->setWidth($width);
        $track->setHeight($height);
        $track->setHide($hide);
        $track->setNumview($numview);
        $track->setResolution($resolution);

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
        $this->assertEquals($channels, $track->getChannels());
        $this->assertEquals($duration, $track->getDuration());
        $this->assertEquals($width, $track->getWidth());
        $this->assertEquals($height, $track->getHeight());
        $this->assertFalse($hide, $track->getHide());
        $this->assertEquals($numview, $track->getNumview());
        $this->assertEquals($resolution, $track->getResolution());
    }

    public function testMaxSize()
    {
        $size = 5368709120; // 5GB, integer types in 32 bits machines only admit 2GB

        $track = new Track();
        $track->setSize($size);
        $this->assertEquals($size, $track->getSize());
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
        $this->assertTrue($track->containsAnyTag(['t0', 't2']));
        $this->assertTrue($track->containsAnyTag(['t2', 't3']));
        $this->assertFalse($track->containsAnyTag(['t0', 't4']));
        $this->assertTrue($track->containsAllTags(['t1', 't2']));
        $this->assertTrue($track->containsAllTags(['t1']));
        $this->assertFalse($track->containsAllTags(['t0', 't2']));
        $this->assertFalse($track->containsAllTags(['t0', 't1', 't2', 't3']));
    }

    public function testIsOnlyAudio()
    {
        $t1 = new Track();
        $t1->setOnlyAudio(true);

        $this->assertTrue($t1->isOnlyAudio());
        $t1->setOnlyAudio(false);
        $this->assertFalse($t1->isOnlyAudio());
    }

    public function testIncNumview()
    {
        $t1 = new Track();
        $t1->setNumview(5);
        $t1->incNumview();

        $this->assertEquals(6, $t1->getNumview());
    }

    public function testDurationInMinutesAndSeconds()
    {
        $duration = 120;
        $duration_in_minutes_and_seconds1 = ['minutes' => 2, 'seconds' => 0];
        $duration_in_minutes_and_seconds2 = ['minutes' => 5, 'seconds' => 30];

        $t1 = new Track();
        $t1->setDuration($duration);

        $this->assertEquals($duration_in_minutes_and_seconds1, $t1->getDurationInMinutesAndSeconds());

        $t1->setDurationInMinutesAndSeconds($duration_in_minutes_and_seconds2);
        $this->assertEquals($duration_in_minutes_and_seconds2, $t1->getDurationInMinutesAndSeconds());
    }

    public function testIsMaster()
    {
        $t1 = new Track();
        $t1->addTag('master');

        $this->assertTrue($t1->isMaster());

        $t1->removeTag('master');
        $this->assertFalse($t1->isMaster());
    }

    /*public function testRef()
    {
        $t1 = new Track();
        $t2 = new Track();

        $t2->setRef($t1);
        $this->assertEquals(null, $t1->getRef());
        $this->assertEquals($t1, $t2->getRef());
    }*/
}
