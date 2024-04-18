<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Tests\Services;

use Pumukit\BasePlayerBundle\Services\SeriesPlaylistService;
use Pumukit\CoreBundle\Services\i18nService;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Document\ValueObject\Url;
use Pumukit\SchemaBundle\Services\FactoryService;

/**
 * @internal
 *
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
    private $factoryService;
    private $i18nService;

    public function setUp(): void
    {
        parent::setUp();

        $this->mmobjRepo = $this->dm->getRepository(MultimediaObject::class);
        $this->seriesRepo = $this->dm->getRepository(Series::class);
        $this->seriesPlaylistService = self::$kernel->getContainer()->get(SeriesPlaylistService::class);
        $this->factoryService = self::$kernel->getContainer()->get(FactoryService::class);
        $this->i18nService = new i18nService(['en','es'], 'en');

        $series = $this->factoryService->createSeries();

        $multimediaObjects = [
            'published' => $this->factoryService->createMultimediaObject($series),
            'hidden' => $this->factoryService->createMultimediaObject($series),
            'blocked' => $this->factoryService->createMultimediaObject($series),
            'prototype' => $this->factoryService->createMultimediaObject($series),
        ];

        $multimediaObjects['published']->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $multimediaObjects['blocked']->setStatus(MultimediaObject::STATUS_BLOCKED);
        $multimediaObjects['hidden']->setStatus(MultimediaObject::STATUS_HIDDEN);
        $multimediaObjects['prototype']->setStatus(MultimediaObject::STATUS_PROTOTYPE);

        $series2 = $this->factoryService->createSeries();
        $playlistMultimediaObjects = [
            'published' => $this->factoryService->createMultimediaObject($series2),
        ];
        $playlistMultimediaObjects['published']->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $track = $this->generateTrackMedia();
        foreach ($multimediaObjects as $multimediaObject) {
            $multimediaObject->addTrack($track);
        }

        foreach ($playlistMultimediaObjects as $multimediaObject) {
            $multimediaObject->addTrack($track);
            $series->getPlaylist()->addMultimediaObject($multimediaObject);
        }

        $this->dm->flush();
        $this->dm->clear();

        foreach ($multimediaObjects as $key => $multimediaObject) {
            $mmobjs[$key] = $this->mmobjRepo->find($multimediaObject->getId());
        }
        foreach ($playlistMultimediaObjects as $key => $multimediaObject) {
            $playlistMmobjs[$key] = $this->mmobjRepo->find($multimediaObject->getId());
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
        static::assertEquals([
            $this->testMmobjs['published'],
            $this->testMmobjs['hidden'],
            $this->testMmobjs['blocked'],
            $this->testPlaylistMmobjs['published'],
        ], iterator_to_array($playlistMmobjs, false));
    }

    public function testGetPlaylistFirstMmobj()
    {
        $playlistMmobj = $this->seriesPlaylistService->getPlaylistFirstMmobj($this->testSeries);
        static::assertEquals($this->testMmobjs['published'], $playlistMmobj);
    }

    public function testGetMmobjFromIdAndPlaylist()
    {
        $playlistMmobj = $this->seriesPlaylistService->getMmobjFromIdAndPlaylist($this->testMmobjs['published']->getId(), $this->testSeries);
        static::assertEquals($this->testMmobjs['published'], $playlistMmobj);
    }

    private function generateTrackMedia(): MediaInterface
    {
        $originalName = 'originalName'.rand();
        $description = i18nText::create($this->i18nService->generateI18nText('18nDescription'));
        $language = 'en';
        $tags = Tags::create(['display']);
        $views = 0;
        $url = Url::create('');
        $path = Path::create('public/storage');
        $storage = Storage::create($url, $path);
        $mediaMetadata = VideoAudio::create("{\"format\":{\"duration\":\"10.000000\"}}");

        return Track::create(
            $originalName,
            $description,
            $language,
            $tags,
            false,
            true,
            $views,
            $storage,
            $mediaMetadata
        );
    }
}
