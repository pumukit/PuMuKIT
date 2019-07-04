<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Playlist;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PlaylistTest extends WebTestCase
{
    public function testCreate()
    {
        $playlist = new Playlist();
        $this->assertEquals([], $playlist->getMultimediaObjectsIdList());
    }

    public function testMoveMultimediaObject()
    {
        $playlist = new Series();
        $mmobjA = new MultimediaObject();
        $mmobjB = new MultimediaObject();
        $mmobjC = new MultimediaObject();
        $mmobjA->setSeries($playlist);
        $mmobjB->setSeries($playlist);
        $mmobjC->setSeries($playlist);
        $this->assertEquals(0, $playlist->getPlaylist()->getMultimediaObjects()->count());
        $playlist->getPlaylist()->addMultimediaObject($mmobjA);
        $playlist->getPlaylist()->addMultimediaObject($mmobjB);
        $playlist->getPlaylist()->addMultimediaObject($mmobjC);
        $playlist->getPlaylist()->addMultimediaObject($mmobjA);
        $playlist->getPlaylist()->addMultimediaObject($mmobjB);
        $playlist->getPlaylist()->addMultimediaObject($mmobjC);
        //Nothing changes
        $oldArray = $playlist->getPlaylist()->getMultimediaObjects()->toArray();
        $this->assertFalse(false, $playlist->getPlaylist()->moveMultimediaObject(123, 123));
        $this->assertEquals($oldArray, $playlist->getPlaylist()->getMultimediaObjects()->toArray());
        //Start out of bounds (nothing changes either).
        $this->assertFalse($playlist->getPlaylist()->moveMultimediaObject(-123, 0));
        $mmobjs = [
            $mmobjA,
            $mmobjB,
            $mmobjC,
            $mmobjA,
            $mmobjB,
            $mmobjC,
        ];
        $this->assertEquals($mmobjs, $playlist->getPlaylist()->getMultimediaObjects()->toArray());
        //Move one.
        $playlist->getPlaylist()->moveMultimediaObject(3, 1);
        $mmobjs = [
            $mmobjA,
            $mmobjA,
            $mmobjB,
            $mmobjC,
            $mmobjB,
            $mmobjC,
        ];
        $this->assertEquals($mmobjs, $playlist->getPlaylist()->getMultimediaObjects()->toArray());
        //Downwards out of bounds (goes in a circle)
        $playlist->getPlaylist()->moveMultimediaObject(4, 9);
        $mmobjs = [
            $mmobjA,
            $mmobjA,
            $mmobjB,
            $mmobjB,
            $mmobjC,
            $mmobjC,
        ];
        $this->assertEquals($mmobjs, $playlist->getPlaylist()->getMultimediaObjects()->toArray());
        //Move upward
        $playlist->getPlaylist()->moveMultimediaObject(5, 0);
        $mmobjs = [
            $mmobjC,
            $mmobjA,
            $mmobjA,
            $mmobjB,
            $mmobjB,
            $mmobjC,
        ];
        $this->assertEquals($mmobjs, $playlist->getPlaylist()->getMultimediaObjects()->toArray());
        //Upwards out of bounds
        $playlist->getPlaylist()->moveMultimediaObject(0, -1);
        $mmobjs = [
            $mmobjA,
            $mmobjA,
            $mmobjB,
            $mmobjB,
            $mmobjC,
            $mmobjC,
        ];
        $this->assertEquals($mmobjs, $playlist->getPlaylist()->getMultimediaObjects()->toArray());
        //Upwards REALLY out of bounds
        $playlist->getPlaylist()->moveMultimediaObject(2, 1 - 12);
        $mmobjs = [
            $mmobjA,
            $mmobjB,
            $mmobjA,
            $mmobjB,
            $mmobjC,
            $mmobjC,
        ];
        $this->assertEquals($mmobjs, $playlist->getPlaylist()->getMultimediaObjects()->toArray());
        //Downwards REALLY out of bounds
        $playlist->getPlaylist()->moveMultimediaObject(3, 7 + 12);
        $mmobjs = [
            $mmobjA,
            $mmobjB,
            $mmobjB,
            $mmobjA,
            $mmobjC,
            $mmobjC,
        ];
        $this->assertEquals($mmobjs, $playlist->getPlaylist()->getMultimediaObjects()->toArray());

        $this->assertEquals(false, false);
    }
}
