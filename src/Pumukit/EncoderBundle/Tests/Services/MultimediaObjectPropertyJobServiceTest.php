<?php

namespace Pumukit\EncoderBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\EncoderBundle\Document\Job;

class MultimediaObjectPropertyJobServiceTest extends WebTestCase
{
    private $dm;

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);
        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->service = static::$kernel->getContainer()->get('pumukitencoder.mmpropertyjob');

        $this->dm->getDocumentCollection('PumukitEncoderBundle:Job')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testService()
    {
        $mm = new MultimediaObject();
        $job = new Job();
        $otherJob = new Job();

        $this->dm->persist($mm);
        $this->dm->persist($job);
        $this->dm->persist($otherJob);
        $this->dm->flush();

        $this->assertEquals(null, $mm->getProperty('pending_jobs'));
        $this->assertEquals(null, $mm->getProperty('executing_jobs'));
        $this->assertEquals(null, $mm->getProperty('finished_jobs'));
        $this->assertEquals(null, $mm->getProperty('error_jobs'));

        $this->service->addJob($mm, $job);
        $this->assertEquals(array($job->getId()), $mm->getProperty('pending_jobs'));
        $this->assertEquals(null, $mm->getProperty('executing_jobs'));
        $this->assertEquals(null, $mm->getProperty('finished_jobs'));
        $this->assertEquals(null, $mm->getProperty('error_jobs'));

        $this->service->addJob($mm, $otherJob);
        $this->assertEquals(array($job->getId(), $otherJob->getId()), $mm->getProperty('pending_jobs'));
        $this->assertEquals(null, $mm->getProperty('executing_jobs'));
        $this->assertEquals(null, $mm->getProperty('finished_jobs'));
        $this->assertEquals(null, $mm->getProperty('error_jobs'));

        $this->service->executeJob($mm, $job);
        $this->assertEquals(array($otherJob->getId()), $mm->getProperty('pending_jobs'));
        $this->assertEquals(array($job->getId()), $mm->getProperty('executing_jobs'));
        $this->assertEquals(null, $mm->getProperty('finished_jobs'));
        $this->assertEquals(null, $mm->getProperty('error_jobs'));

        $this->service->finishJob($mm, $job);
        $this->assertEquals(array($otherJob->getId()), $mm->getProperty('pending_jobs'));
        $this->assertEquals(null, $mm->getProperty('executing_jobs'));
        $this->assertEquals(array($job->getId()), $mm->getProperty('finished_jobs'));
        $this->assertEquals(null, $mm->getProperty('error_jobs'));

        $this->service->finishJob($mm, $otherJob); //Invalid step. No properties change.
        $this->assertEquals(array($otherJob->getId()), $mm->getProperty('pending_jobs'));
        $this->assertEquals(null, $mm->getProperty('executing_jobs'));
        $this->assertEquals(array($job->getId()), $mm->getProperty('finished_jobs'));
        $this->assertEquals(null, $mm->getProperty('error_jobs'));

        $this->service->executeJob($mm, $otherJob);
        $this->assertEquals(null, $mm->getProperty('pending_jobs'));
        $this->assertEquals(array($otherJob->getId()), $mm->getProperty('executing_jobs'));
        $this->assertEquals(array($job->getId()), $mm->getProperty('finished_jobs'));
        $this->assertEquals(null, $mm->getProperty('error_jobs'));

        $this->service->errorJob($mm, $otherJob);
        $this->assertEquals(null, $mm->getProperty('pending_jobs'));
        $this->assertEquals(null, $mm->getProperty('executing_jobs'));
        $this->assertEquals(array($job->getId()), $mm->getProperty('finished_jobs'));
        $this->assertEquals(array($otherJob->getId()), $mm->getProperty('error_jobs'));
    }
}
