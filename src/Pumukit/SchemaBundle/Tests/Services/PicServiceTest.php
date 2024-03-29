<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\EventListener\MultimediaObjectListener;
use Pumukit\SchemaBundle\Services\PicService;
use Pumukit\SchemaBundle\Services\TextIndexService;
use Pumukit\SchemaBundle\Services\TrackService;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 *
 * @coversNothing
 */
class PicServiceTest extends PumukitTestCase
{
    private $factoryService;
    private $picService;
    private $defaultSeriesPic = '/images/series.jpg';
    private $defaultPlaylistPic = '/images/playlist.jpg';
    private $defaultVideoPic = '/images/video.jpg';
    private $defaultAudioHDPic = '/images/audio_hd.jpg';
    private $defaultAudioSDPic = '/images/audio_sd.jpg';
    private $webDir;
    private $listener;
    private $trackDispatcher;
    private $trackService;
    private $absoluteDomain;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        parent::setUp();
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');

        $publicDir = static::$kernel->getContainer()->getParameter('pumukit.public_dir');
        $scheme = static::$kernel->getContainer()->getParameter('router.request_context.scheme');
        $host = static::$kernel->getContainer()->getParameter('router.request_context.host');
        $this->webDir = realpath($publicDir.'/bundles/pumukitschema');
        $this->absoluteDomain = str_replace("'", '', $scheme).'://'.str_replace("'", '', $host);

        $this->picService = new PicService($scheme, $host, $this->webDir, $this->defaultSeriesPic, $this->defaultPlaylistPic, $this->defaultVideoPic, $this->defaultAudioHDPic, $this->defaultAudioSDPic);

        $dispatcher = new EventDispatcher();
        $this->listener = new MultimediaObjectListener($this->dm, new TextIndexService());
        $dispatcher->addListener('multimediaobject.update', [$this->listener, 'postUpdate']);
        $this->trackDispatcher = static::$kernel->getContainer()->get('pumukitschema.track_dispatcher');
        $this->trackService = new TrackService($this->dm, $this->trackDispatcher, null, true);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->factoryService = null;
        $this->webDir = null;
        $this->picService = null;
        $this->listener = null;
        $this->trackDispatcher = null;
        $this->trackService = null;
        gc_collect_cycles();
    }

    public function testGetDefaultUrlPicForObject()
    {
        $pic = new Pic();

        $absolute = false;
        static::assertEquals($this->defaultVideoPic, $this->picService->getDefaultUrlPicForObject($pic, $absolute));

        $absolute = true;
        static::assertEquals($this->absoluteDomain.$this->defaultVideoPic, $this->picService->getDefaultUrlPicForObject($pic, $absolute));
    }

    public function testGetDefaultPathPicForObject()
    {
        $pic = new Pic();

        static::assertEquals($this->webDir.$this->defaultVideoPic, $this->picService->getDefaultPathPicForObject($pic));
    }

    public function testGetFirstUrlPic()
    {
        // SERIES SECTION
        $series = $this->factoryService->createSeries();

        $absolute = false;
        static::assertEquals($this->defaultSeriesPic, $this->picService->getFirstUrlPic($series, $absolute));

        $absolute = true;
        static::assertEquals($this->absoluteDomain.$this->defaultSeriesPic, $this->picService->getFirstUrlPic($series, $absolute));

        $seriesUrl1 = '/uploads/series1.jpg';
        $seriesPic1 = new Pic();
        $seriesPic1->setUrl($seriesUrl1);

        $series->addPic($seriesPic1);

        $this->dm->persist($series);
        $this->dm->flush();

        static::assertEquals($seriesUrl1, $this->picService->getFirstUrlPic($series));

        $seriesUrl2 = '/uploads/series2.jpg';
        $seriesPic2 = new Pic();
        $seriesPic2->setUrl($seriesUrl2);

        $series->addPic($seriesPic2);

        $this->dm->persist($series);
        $this->dm->flush();

        $series->upPicById($seriesPic2->getId());

        $this->dm->persist($series);
        $this->dm->flush();

        static::assertEquals($seriesUrl2, $this->picService->getFirstUrlPic($series));

        $mm = $this->factoryService->createMultimediaObject($series);
        $mm->setSeries($series);
        $this->dm->persist($mm);
        $this->dm->flush();

        $track = new Track();
        $track->setOnlyAudio(false);
        $this->trackService->addTrackToMultimediaObject($mm, $track, true);

        $absolute = false;
        static::assertEquals($this->defaultVideoPic, $this->picService->getFirstUrlPic($mm, $absolute));

        $absolute = true;
        static::assertEquals($this->absoluteDomain.$this->defaultVideoPic, $this->picService->getFirstUrlPic($mm, $absolute));

        $track->setOnlyAudio(true);
        $track->addTag('master');
        $this->trackService->updateTrackInMultimediaObject($mm, $track, true);

        $absolute = false;
        $hd = true;
        static::assertEquals($this->defaultAudioHDPic, $this->picService->getFirstUrlPic($mm, $absolute, $hd));
        $hd = false;
        static::assertEquals($this->defaultAudioSDPic, $this->picService->getFirstUrlPic($mm, $absolute, $hd));

        $absolute = true;
        $hd = true;
        static::assertEquals($this->absoluteDomain.$this->defaultAudioHDPic, $this->picService->getFirstUrlPic($mm, $absolute, $hd));
        $hd = false;
        static::assertEquals($this->absoluteDomain.$this->defaultAudioSDPic, $this->picService->getFirstUrlPic($mm, $absolute, $hd));

        $mmUrl1 = '/uploads/video1.jpg';
        $mmPic1 = new Pic();
        $mmPic1->setUrl($mmUrl1);

        $mm->addPic($mmPic1);

        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertEquals($mmUrl1, $this->picService->getFirstUrlPic($mm));

        $absolute = true;
        static::assertEquals($this->absoluteDomain.$mmUrl1, $this->picService->getFirstUrlPic($mm, $absolute));

        $mmUrl2 = '/uploads/video2.jpg';
        $mmPic2 = new Pic();
        $mmPic2->setUrl($mmUrl2);

        $mm->addPic($mmPic2);

        $this->dm->persist($mm);

        $mm->upPicById($mmPic2->getId());

        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertEquals($mmUrl2, $this->picService->getFirstUrlPic($mm));

        $absolute = true;
        static::assertEquals($this->absoluteDomain.$mmUrl2, $this->picService->getFirstUrlPic($mm, $absolute));
    }

    public function testGetFirstPathPic()
    {
        // SERIES SECTION
        $series = $this->factoryService->createSeries();

        static::assertEquals($this->webDir.$this->defaultSeriesPic, $this->picService->getFirstPathPic($series));

        $seriesPath1 = $this->webDir.'/uploads/series1.jpg';
        $seriesPic1 = new Pic();
        $seriesPic1->setPath($seriesPath1);

        $series->addPic($seriesPic1);

        $this->dm->persist($series);
        $this->dm->flush();

        static::assertEquals($seriesPath1, $this->picService->getFirstPathPic($series));

        $seriesPath2 = $this->webDir.'/uploads/series2.jpg';
        $seriesPic2 = new Pic();
        $seriesPic2->setPath($seriesPath2);

        $series->addPic($seriesPic2);

        $this->dm->persist($series);

        $series->upPicById($seriesPic2->getId());

        $this->dm->persist($series);
        $this->dm->flush();

        static::assertEquals($seriesPath2, $this->picService->getFirstPathPic($series));

        // MULTIMEDIA OBJECT SECTION
        // Workaround for detached Series document
        $this->dm->clear();
        $series = $this->dm->find(Series::class, $series->getId());

        $mm = $this->factoryService->createMultimediaObject($series);
        $mm->setSeries($series);
        $this->dm->persist($mm);
        $this->dm->flush();

        $track = new Track();
        $track->setOnlyAudio(false);
        $this->trackService->addTrackToMultimediaObject($mm, $track, true);

        static::assertEquals($this->webDir.$this->defaultVideoPic, $this->picService->getFirstPathPic($mm));

        $track->setOnlyAudio(true);
        $track->addTag('master');
        $this->trackService->updateTrackInMultimediaObject($mm, $track, true);

        $hd = true;
        static::assertEquals($this->webDir.$this->defaultAudioHDPic, $this->picService->getFirstPathPic($mm, $hd));
        $hd = false;
        static::assertEquals($this->webDir.$this->defaultAudioSDPic, $this->picService->getFirstPathPic($mm, $hd));

        $mmPath1 = realpath(__DIR__.'/../Resources/images/video_none.jpg');
        $mmPic1 = new Pic();
        $mmPic1->setPath($mmPath1);

        $mm->addPic($mmPic1);

        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertEquals($mmPath1, $this->picService->getFirstPathPic($mm));

        $mmPath2 = realpath(__DIR__.'/../Resources/images/series_folder.png');
        $mmPic2 = new Pic();
        $mmPic2->setPath($mmPath2);

        $mm->addPic($mmPic2);

        $this->dm->persist($mm);

        $mm->upPicById($mmPic2->getId());

        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertEquals($mmPath2, $this->picService->getFirstPathPic($mm));
    }
}
