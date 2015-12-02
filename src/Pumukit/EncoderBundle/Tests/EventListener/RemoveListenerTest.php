<?php

namespace Pumukit\EncoderBundle\Tests\EventListener;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\EncoderBundle\Services\CpuService;
use Pumukit\EncoderBundle\Services\JobService;

class RemoveListenerTest extends WebTestCase
{
    private $dm;
    private $repoJobs;
    private $repoMmobj;
    private $repoSeries;
    private $trackService;
    private $factoryService;
    private $resourcesDir;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->logger = $kernel->getContainer()
          ->get('logger');
        $this->dm = $kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
        $this->repoJobs = $this->dm
          ->getRepository('PumukitEncoderBundle:Job');
        $this->repoMmobj = $this->dm
          ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->repoSeries = $this->dm
          ->getRepository('PumukitSchemaBundle:Series');
        $this->factoryService = $kernel->getContainer()
          ->get('pumukitschema.factory');
        $this->trackService = $kernel->getContainer()
          ->get('pumukitschema.track');
        $this->tokenStorage = $kernel->getContainer()
          ->get('security.token_storage');

        $this->resourcesDir = realpath(__DIR__.'/../Resources');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')
          ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')
          ->remove(array());
        $this->dm->getDocumentCollection('PumukitEncoderBundle:Job')
          ->remove(array());
        $this->dm->flush();
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

    private function createJobWithStatusAndPathEnd($status=Job::STATUS_WAITING, $multimediaObject, $pathEnd)
    {
        $job = new Job();
        $job->setMmId($multimediaObject->getId());
        $job->setPathEnd($pathEnd);
        $job->setStatus($status);
        $this->dm->persist($job);
        $this->dm->flush();
    }
}