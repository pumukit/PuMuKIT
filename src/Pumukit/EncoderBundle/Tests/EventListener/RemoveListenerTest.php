<?php

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
    private $resourcesDir;
    private $tokenStorage;

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
        $this->tokenStorage = static::$kernel->getContainer()->get('security.token_storage');

        $this->resourcesDir = realpath(__DIR__.'/../Resources');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();
        $this->dm = null;
        $this->repoJobs = null;
        $this->repoMmobj = null;
        $this->repoSeries = null;
        $this->factoryService = null;
        $this->trackService = null;
        $this->tokenStorage = null;
        $this->resourcesDir = null;
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

        $this->assertEquals(1, count($this->repoSeries->findAll()));
        $this->assertEquals(2, count($this->repoMmobj->findAll()));
        $this->assertEquals(1, count($this->repoJobs->findAll()));

        $this->trackService->removeTrackFromMultimediaObject($multimediaObject, $track->getId());

        $this->assertEquals(1, count($this->repoSeries->findAll()));
        $this->assertEquals(2, count($this->repoMmobj->findAll()));
        $this->assertEquals(0, count($this->repoJobs->findAll()));
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
