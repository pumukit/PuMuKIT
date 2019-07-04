<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\CpuService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Services\TrackService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class TrackServiceTest extends WebTestCase
{
    private $dm;
    private $repoJobs;
    private $repoMmobj;
    private $trackService;
    private $factoryService;
    private $logger;
    private $tokenStorage;
    private $trackDispatcher;
    private $tmpDir;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->logger = static::$kernel->getContainer()
            ->get('logger')
        ;
        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repoJobs = $this->dm
            ->getRepository(Job::class)
        ;
        $this->repoMmobj = $this->dm
            ->getRepository(MultimediaObject::class)
        ;
        $this->factoryService = static::$kernel->getContainer()
            ->get('pumukitschema.factory')
        ;
        $this->trackDispatcher = static::$kernel->getContainer()
            ->get('pumukitschema.track_dispatcher')
        ;
        $this->tokenStorage = static::$kernel->getContainer()
            ->get('security.token_storage')
        ;

        $this->dm->getDocumentCollection(MultimediaObject::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection('PumukitSchemaBundle:SeriesType')
            ->remove([])
        ;
        $this->dm->getDocumentCollection(Series::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection(Job::class)
            ->remove([])
        ;
        $this->dm->flush();

        $profileService = new ProfileService($this->getDemoProfiles(), $this->dm);
        $this->trackService = new TrackService($this->dm, $this->trackDispatcher, $profileService, null, true);

        $this->tmpDir = $this->trackService->getTempDirs()[0];
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->logger = null;
        $this->dm = null;
        $this->repoJobs = null;
        $this->repoMmobj = null;
        $this->factoryService = null;
        $this->trackDispatcher = null;
        $this->tokenStorage = null;
        $this->trackService = null;
        $this->tmpDir = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testAddTrackToMultimediaObject()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $track = new Track();
        $multimediaObject = $this->trackService->addTrackToMultimediaObject($multimediaObject, $track);

        $multimediaObject = $this->repoMmobj->find($multimediaObject->getId());
        $embeddedTrack = $multimediaObject->getTrackById($track->getId());
        $this->assertEquals($track, $embeddedTrack);
    }

    public function testUpdateTrackInMultimediaObject()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $url = 'uploads/tracks/track.mp4';

        $track = new Track();
        $track->setUrl($url);

        $multimediaObject->addTrack($track);

        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $multimediaObject = $this->repoMmobj->find($multimediaObject->getId());
        $track = $multimediaObject->getTracks()[0];
        $this->assertEquals($url, $track->getUrl());

        $newUrl = 'uploads/tracks/track2.mp4';
        $track->setUrl($newUrl);

        $this->trackService->updateTrackInMultimediaObject($multimediaObject, $track);
        $multimediaObject = $this->repoMmobj->find($multimediaObject->getId());
        $track = $multimediaObject->getTracks()[0];
        $this->assertEquals($newUrl, $track->getUrl());
    }

    public function testRemoveTrackFromMultimediaObject()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getTracks()));
        $this->assertEquals(0, count($this->repoJobs->findAll()));

        $job = new Job();
        $job->setMmId($multimediaObject->getId());
        $job->setStatus(Job::STATUS_FINISHED);
        $job->setProfile('master');

        $track = new Track();
        $track->addTag('profile:master');
        $multimediaObject->addTrack($track);

        $this->dm->persist($job);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->assertEquals(1, count($multimediaObject->getTracks()));
        $this->assertEquals(1, count($this->repoJobs->findAll()));

        $multimediaObject = $this->repoMmobj->find($multimediaObject->getId());
        $track = $multimediaObject->getTracks()[0];

        $this->trackService->removeTrackFromMultimediaObject($multimediaObject, $track->getId());

        $this->assertEquals(0, count($multimediaObject->getTracks()));
        $this->assertEquals(0, count($this->repoJobs->findAll()));
    }

    public function testUpAndDownTrackInMultimediaObject()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getTracks()));

        $track1 = new Track();
        $track2 = new Track();
        $track3 = new Track();
        $track4 = new Track();
        $track5 = new Track();

        $multimediaObject->addTrack($track1);
        $multimediaObject->addTrack($track2);
        $multimediaObject->addTrack($track3);
        $multimediaObject->addTrack($track4);
        $multimediaObject->addTrack($track5);

        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $multimediaObject = $this->repoMmobj->find($multimediaObject->getId());
        $tracks = $multimediaObject->getTracks();
        $track1 = $tracks[0];
        $track2 = $tracks[1];
        $track3 = $tracks[2];
        $track4 = $tracks[3];
        $track5 = $tracks[4];

        $this->assertEquals(5, count($multimediaObject->getTracks()));

        $arrayTracks = [$track1, $track2, $track3, $track4, $track5];
        $this->assertEquals($arrayTracks, $multimediaObject->getTracks()->toArray());

        $multimediaObject = $this->trackService->upTrackInMultimediaObject($multimediaObject, $track3->getId());
        $multimediaObject = $this->repoMmobj->find($multimediaObject->getId());

        $arrayTracks = [$track1, $track3, $track2, $track4, $track5];
        $this->assertEquals($arrayTracks, $multimediaObject->getTracks()->toArray());

        $multimediaObject = $this->trackService->downTrackInMultimediaObject($multimediaObject, $track4->getId());
        $multimediaObject = $this->repoMmobj->find($multimediaObject->getId());

        $arrayTracks = [$track1, $track3, $track2, $track5, $track4];
        $this->assertEquals($arrayTracks, $multimediaObject->getTracks()->toArray());
    }

    private function createFormData($number)
    {
        return [
            'profile' => 'MASTER_COPY',
            'priority' => 2,
            'language' => 'en',
            'i18n_description' => [
                'en' => 'track description '.$number,
                'es' => 'descripción del archivo '.$number,
            ],
        ];
    }

    private function getDemoCpus()
    {
        return [
            'CPU_LOCAL' => [
                'id' => 1,
                'host' => '127.0.0.1',
                'max' => 1,
                'number' => 1,
                'type' => CpuService::TYPE_LINUX,
                'user' => 'transco1',
                'password' => 'PUMUKIT',
                'description' => 'Pumukit transcoder',
            ],
            'CPU_REMOTE' => [
                'id' => 2,
                'host' => '192.168.5.123',
                'max' => 2,
                'number' => 1,
                'type' => CpuService::TYPE_LINUX,
                'user' => 'transco2',
                'password' => 'PUMUKIT',
                'description' => 'Pumukit transcoder',
            ],
        ];
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
                    'dir_out' => __DIR__.'/../Resources/dir_out',                                                         ],
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
