<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Pumukit\SchemaBundle\Services\PicService;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Services\TrackService;
use Pumukit\SchemaBundle\EventListener\MultimediaObjectListener;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class PicServiceTest extends WebTestCase
{
    private $dm;
    private $factoryService;
    private $picService;
    private $context;
    private $defaultSeriesPic = '/images/series.jpg';
    private $defaultPlaylistPic = '/images/playlist.jpg';
    private $defaultVideoPic = '/images/video.jpg';
    private $defaultAudioHDPic = '/images/audio_hd.jpg';
    private $defaultAudioSDPic = '/images/audio_sd.jpg';
    private $localhost = 'http://localhost';
    private $webDir;
    private $listener;
    private $trackDispatcher;
    private $trackService;
    private $rootDir;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');
        $this->context = static::$kernel->getContainer()->get('router.request_context');
        $this->rootDir = static::$kernel->getContainer()->getParameter('kernel.root_dir');
        $this->webDir = realpath($this->rootDir.'/../web/bundles/pumukitschema');
        $this->localhost = $this->context->getScheme().'://localhost';

        $this->dm->getDocumentCollection(MultimediaObject::class)->remove([]);
        $this->dm->getDocumentCollection(Series::class)->remove([]);
        $this->dm->flush();

        $this->picService = new PicService($this->context, $this->webDir, $this->defaultSeriesPic, $this->defaultPlaylistPic, $this->defaultVideoPic, $this->defaultAudioHDPic, $this->defaultAudioSDPic);

        $dispatcher = new EventDispatcher();
        $this->listener = new MultimediaObjectListener($this->dm);
        $dispatcher->addListener('multimediaobject.update', [$this->listener, 'postUpdate']);
        $this->trackDispatcher = static::$kernel->getContainer()
          ->get('pumukitschema.track_dispatcher');
        $profileService = new ProfileService($this->getDemoProfiles(), $this->dm);
        $this->trackService = new TrackService($this->dm, $this->trackDispatcher, $profileService, null, true);
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->factoryService = null;
        $this->context = null;
        $this->rootDir = null;
        $this->webDir = null;
        $this->localhost = null;
        $this->picService = null;
        $this->listener = null;
        $this->trackDispatcher = null;
        $this->trackService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testGetDefaultUrlPicForObject()
    {
        $pic = new Pic();

        $absolute = false;
        $this->assertEquals($this->defaultVideoPic, $this->picService->getDefaultUrlPicForObject($pic, $absolute));

        $absolute = true;
        $this->assertEquals($this->localhost.$this->defaultVideoPic, $this->picService->getDefaultUrlPicForObject($pic, $absolute));
    }

    public function testGetDefaultPathPicForObject()
    {
        $pic = new Pic();

        $this->assertEquals($this->webDir.$this->defaultVideoPic, $this->picService->getDefaultPathPicForObject($pic));
    }

    public function testGetFirstUrlPic()
    {
        // SERIES SECTION
        $series = $this->factoryService->createSeries();

        $absolute = false;
        $this->assertEquals($this->defaultSeriesPic, $this->picService->getFirstUrlPic($series, $absolute));

        $absolute = true;
        $this->assertEquals($this->localhost.$this->defaultSeriesPic, $this->picService->getFirstUrlPic($series, $absolute));

        $seriesUrl1 = '/uploads/series1.jpg';
        $seriesPic1 = new Pic();
        $seriesPic1->setUrl($seriesUrl1);

        $series->addPic($seriesPic1);

        $this->dm->persist($series);
        $this->dm->flush();

        $this->assertEquals($seriesUrl1, $this->picService->getFirstUrlPic($series));

        $seriesUrl2 = '/uploads/series2.jpg';
        $seriesPic2 = new Pic();
        $seriesPic2->setUrl($seriesUrl2);

        $series->addPic($seriesPic2);

        $this->dm->persist($series);
        $this->dm->flush();

        $series->upPicById($seriesPic2->getId());

        $this->dm->persist($series);
        $this->dm->flush();

        $this->assertEquals($seriesUrl2, $this->picService->getFirstUrlPic($series));

        $mm = $this->factoryService->createMultimediaObject($series);
        $mm->setSeries($series);
        $this->dm->persist($mm);
        $this->dm->flush();

        $track = new Track();
        $track->setOnlyAudio(false);
        $this->trackService->addTrackToMultimediaObject($mm, $track, true);

        $absolute = false;
        $this->assertEquals($this->defaultVideoPic, $this->picService->getFirstUrlPic($mm, $absolute));

        $absolute = true;
        $this->assertEquals($this->localhost.$this->defaultVideoPic, $this->picService->getFirstUrlPic($mm, $absolute));

        $track->setOnlyAudio(true);
        $track->addTag('master');
        $this->trackService->updateTrackInMultimediaObject($mm, $track, true);

        $absolute = false;
        $hd = true;
        $this->assertEquals($this->defaultAudioHDPic, $this->picService->getFirstUrlPic($mm, $absolute, $hd));
        $hd = false;
        $this->assertEquals($this->defaultAudioSDPic, $this->picService->getFirstUrlPic($mm, $absolute, $hd));

        $absolute = true;
        $hd = true;
        $this->assertEquals($this->localhost.$this->defaultAudioHDPic, $this->picService->getFirstUrlPic($mm, $absolute, $hd));
        $hd = false;
        $this->assertEquals($this->localhost.$this->defaultAudioSDPic, $this->picService->getFirstUrlPic($mm, $absolute, $hd));

        $mmUrl1 = '/uploads/video1.jpg';
        $mmPic1 = new Pic();
        $mmPic1->setUrl($mmUrl1);

        $mm->addPic($mmPic1);

        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertEquals($mmUrl1, $this->picService->getFirstUrlPic($mm));

        $absolute = true;
        $this->assertEquals($this->localhost.$mmUrl1, $this->picService->getFirstUrlPic($mm, $absolute));

        $mmUrl2 = '/uploads/video2.jpg';
        $mmPic2 = new Pic();
        $mmPic2->setUrl($mmUrl2);

        $mm->addPic($mmPic2);

        $this->dm->persist($mm);

        $mm->upPicById($mmPic2->getId());

        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertEquals($mmUrl2, $this->picService->getFirstUrlPic($mm));

        $absolute = true;
        $this->assertEquals($this->localhost.$mmUrl2, $this->picService->getFirstUrlPic($mm, $absolute));
    }

    public function testGetFirstPathPic()
    {
        // SERIES SECTION
        $series = $this->factoryService->createSeries();

        $this->assertEquals($this->webDir.$this->defaultSeriesPic, $this->picService->getFirstPathPic($series));

        $seriesPath1 = $this->webDir.'/uploads/series1.jpg';
        $seriesPic1 = new Pic();
        $seriesPic1->setPath($seriesPath1);

        $series->addPic($seriesPic1);

        $this->dm->persist($series);
        $this->dm->flush();

        $this->assertEquals($seriesPath1, $this->picService->getFirstPathPic($series));

        $seriesPath2 = $this->webDir.'/uploads/series2.jpg';
        $seriesPic2 = new Pic();
        $seriesPic2->setPath($seriesPath2);

        $series->addPic($seriesPic2);

        $this->dm->persist($series);

        $series->upPicById($seriesPic2->getId());

        $this->dm->persist($series);
        $this->dm->flush();

        $this->assertEquals($seriesPath2, $this->picService->getFirstPathPic($series));

        // MULTIMEDIA OBJECT SECTION
        // Workaround for detached Series document
        $this->dm->clear(get_class($series));
        $series = $this->dm->find(Series::class, $series->getId());

        $mm = $this->factoryService->createMultimediaObject($series);
        $mm->setSeries($series);
        $this->dm->persist($mm);
        $this->dm->flush();

        $track = new Track();
        $track->setOnlyAudio(false);
        $this->trackService->addTrackToMultimediaObject($mm, $track, true);

        $this->assertEquals($this->webDir.$this->defaultVideoPic, $this->picService->getFirstPathPic($mm));

        $track->setOnlyAudio(true);
        $track->addTag('master');
        $this->trackService->updateTrackInMultimediaObject($mm, $track, true);

        $hd = true;
        $this->assertEquals($this->webDir.$this->defaultAudioHDPic, $this->picService->getFirstPathPic($mm, $hd));
        $hd = false;
        $this->assertEquals($this->webDir.$this->defaultAudioSDPic, $this->picService->getFirstPathPic($mm, $hd));

        $mmPath1 = realpath(__DIR__.'/../Resources/images/video_none.jpg');
        $mmPic1 = new Pic();
        $mmPic1->setPath($mmPath1);

        $mm->addPic($mmPic1);

        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertEquals($mmPath1, $this->picService->getFirstPathPic($mm));

        $mmPath2 = realpath(__DIR__.'/../Resources/images/series_folder.png');
        $mmPic2 = new Pic();
        $mmPic2->setPath($mmPath2);

        $mm->addPic($mmPic2);

        $this->dm->persist($mm);

        $mm->upPicById($mmPic2->getId());

        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertEquals($mmPath2, $this->picService->getFirstPathPic($mm));
    }

    private function getDemoProfiles()
    {
        $profiles = [
            'MASTER_COPY' => [
                'display' => false,
                'wizard' => true,
                'master' => true,
                'resolution_hor' => 0,
                'resolution_ver' => 0,
                'framerate' => '0',
                'channels' => 1,
                'audio' => false,
                'bat' => 'cp "{{input}}" "{{output}}"',
                'streamserver' => [
                    'type' => ProfileService::STREAMSERVER_STORE,
                    'host' => '127.0.0.1',
                    'name' => 'Localmaster',
                    'description' => 'Local masters server',
                    'dir_out' => __DIR__.'/../Resources/dir_out',
                ],
                'app' => 'cp',
                'rel_duration_size' => 1,
                'rel_duration_trans' => 1,
            ],
            'MASTER_VIDEO_H264' => [
                'display' => false,
                'wizard' => true,
                'master' => true,
                'format' => 'mp4',
                'codec' => 'h264',
                'mime_type' => 'video/x-mp4',
                'extension' => 'mp4',
                'resolution_hor' => 0,
                'resolution_ver' => 0,
                'bitrate' => '1 Mbps',
                'framerate' => '25/1',
                'channels' => 1,
                'audio' => false,
                'bat' => 'ffmpeg -y -i "{{input}}" -acodec aac -vcodec libx264 -preset slow -crf 15 -threads 0 "{{output}}"',
                'streamserver' => [
                    'type' => ProfileService::STREAMSERVER_STORE,
                    'host' => '192.168.5.125',
                    'name' => 'Download',
                    'description' => 'Download server',
                    'dir_out' => __DIR__.'/../Resources/dir_out',
                    'url_out' => 'http://localhost:8000/downloads/',
                ],
                'app' => 'ffmpeg',
                'rel_duration_size' => 1,
                'rel_duration_trans' => 1,
            ],
        ];

        return $profiles;
    }
}
