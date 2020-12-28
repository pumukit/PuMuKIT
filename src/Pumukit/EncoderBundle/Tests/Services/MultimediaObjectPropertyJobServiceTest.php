<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * @internal
 * @coversNothing
 */
class MultimediaObjectPropertyJobServiceTest extends PumukitTestCase
{
    private $service;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->service = static::$kernel->getContainer()->get('pumukitencoder.mmpropertyjob');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        gc_collect_cycles();
    }

    public function testService()
    {
        $mm = new MultimediaObject();
        $mm->setProperty('test', 'test');
        $job = new Job();
        $otherJob = new Job();

        $this->dm->persist($mm);
        $this->dm->persist($job);
        $this->dm->persist($otherJob);
        $this->dm->flush();
        $mmId = $mm->getId();

        static::assertEquals(null, $mm->getProperty('pending_jobs'));
        static::assertEquals(null, $mm->getProperty('executing_jobs'));
        static::assertEquals(null, $mm->getProperty('finished_jobs'));
        static::assertEquals(null, $mm->getProperty('error_jobs'));

        $this->service->addJob($mm, $job);

        $this->dm->clear('Pumukit\SchemaBundle\Document\MultimediaObject');
        $mm = $this->dm->getRepository(MultimediaObject::class)->find($mmId);

        static::assertEquals([$job->getId()], $mm->getProperty('pending_jobs'));
        static::assertEquals(null, $mm->getProperty('executing_jobs'));
        static::assertEquals(null, $mm->getProperty('finished_jobs'));
        static::assertEquals(null, $mm->getProperty('error_jobs'));

        $this->service->addJob($mm, $otherJob);

        $this->dm->clear('Pumukit\SchemaBundle\Document\MultimediaObject');
        $mm = $this->dm->getRepository(MultimediaObject::class)->find($mmId);

        static::assertEquals([$job->getId(), $otherJob->getId()], $mm->getProperty('pending_jobs'));
        static::assertEquals(null, $mm->getProperty('executing_jobs'));
        static::assertEquals(null, $mm->getProperty('finished_jobs'));
        static::assertEquals(null, $mm->getProperty('error_jobs'));

        $this->service->setJobAsExecuting($mm, $job);

        $this->dm->clear('Pumukit\SchemaBundle\Document\MultimediaObject');
        $mm = $this->dm->getRepository(MultimediaObject::class)->find($mmId);

        static::assertEquals([$otherJob->getId()], $mm->getProperty('pending_jobs'));
        static::assertEquals([$job->getId()], $mm->getProperty('executing_jobs'));
        static::assertEquals(null, $mm->getProperty('finished_jobs'));
        static::assertEquals(null, $mm->getProperty('error_jobs'));

        $this->service->finishJob($mm, $job);

        $this->dm->clear('Pumukit\SchemaBundle\Document\MultimediaObject');
        $mm = $this->dm->getRepository(MultimediaObject::class)->find($mmId);

        static::assertEquals([$otherJob->getId()], $mm->getProperty('pending_jobs'));
        static::assertEquals(null, $mm->getProperty('executing_jobs'));
        static::assertEquals([$job->getId()], $mm->getProperty('finished_jobs'));
        static::assertEquals(null, $mm->getProperty('error_jobs'));

        $this->service->finishJob($mm, $otherJob); //Invalid step. No properties change.

        $this->dm->clear('Pumukit\SchemaBundle\Document\MultimediaObject');
        $mm = $this->dm->getRepository(MultimediaObject::class)->find($mmId);

        static::assertEquals([$otherJob->getId()], $mm->getProperty('pending_jobs'));
        static::assertEquals(null, $mm->getProperty('executing_jobs'));
        static::assertEquals([$job->getId()], $mm->getProperty('finished_jobs'));
        static::assertEquals(null, $mm->getProperty('error_jobs'));

        $this->service->setJobAsExecuting($mm, $otherJob);

        $this->dm->clear('Pumukit\SchemaBundle\Document\MultimediaObject');
        $mm = $this->dm->getRepository(MultimediaObject::class)->find($mmId);

        static::assertEquals(null, $mm->getProperty('pending_jobs'));
        static::assertEquals([$otherJob->getId()], $mm->getProperty('executing_jobs'));
        static::assertEquals([$job->getId()], $mm->getProperty('finished_jobs'));
        static::assertEquals(null, $mm->getProperty('error_jobs'));

        $this->service->errorJob($mm, $otherJob);

        $this->dm->clear('Pumukit\SchemaBundle\Document\MultimediaObject');
        $mm = $this->dm->getRepository(MultimediaObject::class)->find($mmId);

        static::assertEquals(null, $mm->getProperty('pending_jobs'));
        static::assertEquals(null, $mm->getProperty('executing_jobs'));
        static::assertEquals([$job->getId()], $mm->getProperty('finished_jobs'));
        static::assertEquals([$otherJob->getId()], $mm->getProperty('error_jobs'));
    }
}
