<?php

namespace Pumukit\BasePlayerBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Track;

/**
 * @internal
 * @coversNothing
 */
class SeriesPlaylistServiceTest extends PumukitTestCase
{
    private $mmobjRepo;
    private $seriesRepo;
    private $seriesPlaylistService;
    private $testMmobjs;
    private $testPlaylistMmobjs;
    private $testSeries;

    public function setUp(): void
    {
        parent::setUp();

        $this->mmobjRepo = $this->dm->getRepository(MultimediaObject::class);
        $this->seriesRepo = $this->dm->getRepository(Series::class);
        $this->seriesPlaylistService = self::$kernel->getContainer()->get('pumukit_baseplayer.seriesplaylist');

        $track = new Track();
        $series = new Series();
        $series->setNumericalID(1);
        $series2 = new Series();
        $series2->setNumericalID(2);
        $mmobjs = [
            'published' => new MultimediaObject(),
            'hidden' => new MultimediaObject(),
            'blocked' => new MultimediaObject(),
            'prototype' => new MultimediaObject(),
        ];
        $mmobjs['published']->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mmobjs['published']->setNumericalID(1);
        $mmobjs['blocked']->setStatus(MultimediaObject::STATUS_BLOCKED);
        $mmobjs['blocked']->setNumericalID(2);
        $mmobjs['hidden']->setStatus(MultimediaObject::STATUS_HIDDEN);
        $mmobjs['hidden']->setNumericalID(3);
        $mmobjs['prototype']->setStatus(MultimediaObject::STATUS_PROTOTYPE);
        $mmobjs['prototype']->setNumericalID(4);

        $playlistMmobjs = [
            'published' => new MultimediaObject(),
        ];
        $track->setUrl('funnyurl.mp4');
        $playlistMmobjs['published']->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $playlistMmobjs['published']->setNumericalID(5);

        foreach ($mmobjs as $mmobj) {
            $mmobj->setSeries($series);
            $mmobj->addTrack($track);
            $this->dm->persist($mmobj);
        }
        $this->dm->persist($series);
        $this->dm->persist($series2);
        $this->dm->flush();
        foreach ($playlistMmobjs as $mmobj) {
            $mmobj->addTrack($track);
            $mmobj->setSeries($series2);
            $this->dm->persist($mmobj);
            $series->getPlaylist()->addMultimediaObject($mmobj);
        }
        $this->dm->persist($series);
        $this->dm->flush();
        $this->dm->clear();

        foreach ($mmobjs as $key => $mmobj) {
            $mmobjs[$key] = $this->mmobjRepo->find($mmobj->getId());
        }
        foreach ($playlistMmobjs as $key => $mmobj) {
            $playlistMmobjs[$key] = $this->mmobjRepo->find($mmobj->getId());
        }
        $series = $this->seriesRepo->find($series->getId());

        $this->testMmobjs = $mmobjs;
        $this->testPlaylistMmobjs = $playlistMmobjs;
        $this->testSeries = $series;
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();
        $this->dm = null;
        $this->mmobjRepo = null;
        $this->seriesPlaylistService = null;
        $this->testMmobjs = null;
        $this->testPlaylistMmobjs = null;
        $this->testSeries = null;
        gc_collect_cycles();
    }

    public function testGetPlaylistMmobjs()
    {
        $playlistMmobjs = $this->seriesPlaylistService->getPlaylistMmobjs($this->testSeries);
        $this->assertEquals([
            $this->testMmobjs['published'],
            $this->testMmobjs['hidden'],
            $this->testMmobjs['blocked'],
            $this->testPlaylistMmobjs['published'],
        ], iterator_to_array($playlistMmobjs, false));
    }

    public function testGetPlaylistFirstMmobj()
    {
        $playlistMmobj = $this->seriesPlaylistService->getPlaylistFirstMmobj($this->testSeries);
        $this->assertEquals($this->testMmobjs['published'], $playlistMmobj);
    }

    public function testGetMmobjFromIdAndPlaylist()
    {
        $playlistMmobj = $this->seriesPlaylistService->getMmobjFromIdAndPlaylist($this->testMmobjs['published']->getId(), $this->testSeries);
        $this->assertEquals($this->testMmobjs['published'], $playlistMmobj);
    }
}
