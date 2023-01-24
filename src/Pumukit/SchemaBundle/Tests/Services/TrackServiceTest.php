<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Services\TrackService;

/**
 * @internal
 * @coversNothing
 */
class TrackServiceTest extends PumukitTestCase
{
    private $repoJobs;
    private $repoMmobj;
    private $trackService;
    private $factoryService;
    private $trackDispatcher;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();

        $this->repoJobs = $this->dm->getRepository(Job::class);
        $this->repoMmobj = $this->dm->getRepository(MultimediaObject::class);
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');
        $this->trackDispatcher = static::$kernel->getContainer()->get('pumukitschema.track_dispatcher');

        $this->trackService = new TrackService($this->dm, $this->trackDispatcher, null, true);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repoJobs = null;
        $this->repoMmobj = null;
        $this->factoryService = null;
        $this->trackDispatcher = null;
        $this->trackService = null;
        gc_collect_cycles();
    }

    public function testAddTrackToMultimediaObject()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $track = new Track();
        $multimediaObject = $this->trackService->addTrackToMultimediaObject($multimediaObject, $track);

        $multimediaObject = $this->repoMmobj->find($multimediaObject->getId());
        $embeddedTrack = $multimediaObject->getTrackById($track->getId());
        static::assertEquals($track, $embeddedTrack);
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
        static::assertEquals($url, $track->getUrl());

        $newUrl = 'uploads/tracks/track2.mp4';
        $track->setUrl($newUrl);

        $this->trackService->updateTrackInMultimediaObject($multimediaObject, $track);
        $multimediaObject = $this->repoMmobj->find($multimediaObject->getId());
        $track = $multimediaObject->getTracks()[0];
        static::assertEquals($newUrl, $track->getUrl());
    }

    public function testRemoveTrackFromMultimediaObject()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        static::assertCount(0, $multimediaObject->getTracks());
        static::assertCount(0, $this->repoJobs->findAll());

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

        static::assertCount(1, $multimediaObject->getTracks());
        static::assertCount(1, $this->repoJobs->findAll());

        $multimediaObject = $this->repoMmobj->find($multimediaObject->getId());
        $track = $multimediaObject->getTracks()[0];

        $this->trackService->removeTrackFromMultimediaObject($multimediaObject, $track->getId());

        static::assertCount(0, $multimediaObject->getTracks());
        static::assertCount(0, $this->repoJobs->findAll());
    }

    public function testUpAndDownTrackInMultimediaObject()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        static::assertCount(0, $multimediaObject->getTracks());

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

        static::assertCount(5, $multimediaObject->getTracks());

        $arrayTracks = [$track1, $track2, $track3, $track4, $track5];
        static::assertEquals($arrayTracks, $multimediaObject->getTracks()->toArray());

        $multimediaObject = $this->trackService->upTrackInMultimediaObject($multimediaObject, $track3->getId());
        $multimediaObject = $this->repoMmobj->find($multimediaObject->getId());

        $arrayTracks = [$track1, $track3, $track2, $track4, $track5];
        static::assertEquals($arrayTracks, $multimediaObject->getTracks()->toArray());

        $multimediaObject = $this->trackService->downTrackInMultimediaObject($multimediaObject, $track4->getId());
        $multimediaObject = $this->repoMmobj->find($multimediaObject->getId());

        $arrayTracks = [$track1, $track3, $track2, $track5, $track4];
        static::assertEquals($arrayTracks, $multimediaObject->getTracks()->toArray());
    }
}
