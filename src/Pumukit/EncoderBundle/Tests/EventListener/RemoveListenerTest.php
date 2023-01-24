<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\EventListener;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Track;

/**
 * @internal
 * @coversNothing
 */
class RemoveListenerTest extends PumukitTestCase
{
    private $repoJobs;
    private $repoMmobj;
    private $repoSeries;
    private $trackService;
    private $factoryService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        parent::setUp();

        $this->repoJobs = $this->dm->getRepository(Job::class);
        $this->repoMmobj = $this->dm->getRepository(MultimediaObject::class);
        $this->repoSeries = $this->dm->getRepository(Series::class);
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');
        $this->trackService = static::$kernel->getContainer()->get('pumukitschema.track');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repoJobs = null;
        $this->repoMmobj = null;
        $this->repoSeries = null;
        $this->factoryService = null;
        $this->trackService = null;
        gc_collect_cycles();
    }

    public function testPostTrackRemove()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $pathEnd = '/path/to/file.mp4';

        $track = new Track();
        $track->setPath($pathEnd);
        $track->addTag('opencast');
        $multimediaObject->addTrack($track);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->createJobWithStatusAndPathEnd(Job::STATUS_FINISHED, $multimediaObject, $pathEnd);

        static::assertCount(1, $this->repoSeries->findAll());
        static::assertCount(2, $this->repoMmobj->findAll());
        static::assertCount(1, $this->repoJobs->findAll());

        $this->trackService->removeTrackFromMultimediaObject($multimediaObject, $track->getId());

        static::assertCount(1, $this->repoSeries->findAll());
        static::assertCount(2, $this->repoMmobj->findAll());
        static::assertCount(0, $this->repoJobs->findAll());
    }

    private function createJobWithStatusAndPathEnd($status, $multimediaObject, $pathEnd)
    {
        $job = new Job();
        $job->setMmId($multimediaObject->getId());
        $job->setPathEnd($pathEnd);
        $job->setStatus($status);
        $this->dm->persist($job);
        $this->dm->flush();
    }
}
