<?php

namespace Pumukit\SchemaBundle\Tests\EventListener;

use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\EventListener\MultimediaObjectListener;
use Pumukit\SchemaBundle\Services\MultimediaObjectEventDispatcherService;
use Pumukit\SchemaBundle\Services\TrackService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 * @coversNothing
 */
class MultimediaObjectListenerTest extends WebTestCase
{
    private $dm;
    private $mmRepo;
    private $listener;
    private $trackDispatcher;
    private $trackService;
    private $factoryService;
    private $context;
    private $rootDir;
    private $webDir;
    private $localhost;
    private $picService;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->mmRepo = $this->dm->getRepository(MultimediaObject::class);

        $dispatcher = new EventDispatcher();
        // $mmDispatcher = new MultimediaObjectEventDispatcherService($dispatcher);
        $this->listener = new MultimediaObjectListener($this->dm);
        $dispatcher->addListener('multimediaobject.update', [$this->listener, 'postUpdate']);
        $this->trackDispatcher = static::$kernel->getContainer()
            ->get('pumukitschema.track_dispatcher')
        ;
        $profileService = new ProfileService($this->getDemoProfiles(), $this->dm);
        $this->trackService = new TrackService($this->dm, $this->trackDispatcher, $profileService, null, true);

        $this->dm->getDocumentCollection(MultimediaObject::class)
            ->remove([])
        ;
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm = null;
        $this->mmRepo = null;
        $this->listener = null;
        $this->trackDispatcher = null;
        $this->trackService = null;
        $this->factoryService = null;
        $this->context = null;
        $this->rootDir = null;
        $this->webDir = null;
        $this->localhost = null;
        $this->picService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testPostUpdate()
    {
        // MULTIMEDIA OBJECT TEST
        // TEST IS ONLY AUDIO
        $mm = new MultimediaObject();

        $t1 = new Track();
        $t1->setOnlyAudio(true);
        $t2 = new Track();
        $t2->setOnlyAudio(true);
        $t3 = new Track();
        $t3->setOnlyAudio(true);
        $t4 = new Track();
        $t4->setOnlyAudio(true);
        $t5 = new Track();
        $t5->setOnlyAudio(true);
        $t5->addTag('master');

        $this->trackService->addTrackToMultimediaObject($mm, $t1, false);
        $this->trackService->addTrackToMultimediaObject($mm, $t2, false);
        $this->trackService->addTrackToMultimediaObject($mm, $t3, false);
        $this->trackService->addTrackToMultimediaObject($mm, $t4, false);
        $this->trackService->addTrackToMultimediaObject($mm, $t5, true);

        $this->assertTrue($mm->isOnlyAudio());

        $t5->setOnlyAudio(false);

        $this->trackService->updateTrackInMultimediaObject($mm, $t5);

        $this->assertFalse($mm->isOnlyAudio());

        // TEST GET MASTER
        $mm = new MultimediaObject();
        $track3 = new Track();
        $track3->addTag('master');
        $track3->setOnlyAudio(false);
        $track2 = new Track();
        $track2->setOnlyAudio(false);
        $track1 = new Track();
        $track1->setOnlyAudio(true);

        $this->assertEquals(null, $mm->getMaster());
        $this->trackService->addTrackToMultimediaObject($mm, $track1, true);
        $this->assertEquals($track1, $mm->getMaster());
        $this->assertEquals(null, $mm->getMaster(false));
        $this->trackService->addTrackToMultimediaObject($mm, $track2, true);
        $this->assertEquals($track2, $mm->getMaster());
        $this->assertEquals(null, $mm->getMaster(false));
        $this->trackService->addTrackToMultimediaObject($mm, $track3, true);
        $this->assertEquals($track3, $mm->getMaster());
        $this->assertEquals($track3, $mm->getMaster(false));
    }

    private function getDemoProfiles()
    {
        return [
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
    }
}
