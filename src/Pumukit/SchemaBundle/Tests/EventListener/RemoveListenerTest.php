<?php

namespace Pumukit\SchemaBundle\Tests\EventListener;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Series;
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
    private $jobService;
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

    public function testPreRemove()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->createJobWithStatus(Job::STATUS_FINISHED, $multimediaObject);

        $this->assertEquals(1, count($this->repoSeries->findAll()));
        $this->assertEquals(2, count($this->repoMmobj->findAll()));
        $this->assertEquals(1, count($this->repoJobs->findAll()));

        $this->factoryService->deleteResource($multimediaObject);

        $this->assertEquals(1, count($this->repoSeries->findAll()));
        $this->assertEquals(1, count($this->repoMmobj->findAll()));
        $this->assertEquals(0, count($this->repoJobs->findAll()));

    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Can not delete Multimedia Object with id
     */
    public function testPreRemoveWithException()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->createJobWithStatus(Job::STATUS_EXECUTING, $multimediaObject);

        $this->assertEquals(1, count($this->repoSeries->findAll()));
        $this->assertEquals(2, count($this->repoMmobj->findAll()));
        $this->assertEquals(1, count($this->repoJobs->findAll()));

        $this->factoryService->deleteResource($multimediaObject);

        $this->assertEquals(1, count($this->repoSeries->findAll()));
        $this->assertEquals(2, count($this->repoMmobj->findAll()));
        $this->assertEquals(1, count($this->repoJobs->findAll()));


        $this->deleteCreatedFiles();
    }

    private function createJobWithStatus($status=Job::STATUS_WAITING, $multimediaObject)
    {
        $job = new Job();
        $job->setMmId($multimediaObject->getId());
        $job->setStatus($status);
        $this->dm->persist($job);
        $this->dm->flush();
    }

    private function deleteCreatedFiles()
    {
        $mmobjs = $this->repoMmobj->findAll();

        foreach($mmobjs as $mm){
            $mmDir = $this->getDemoProfiles()['MASTER_COPY']['streamserver']['dir_out'].'/'.$mm->getSeries()->getId().'/';
            if (is_dir($mmDir)){
                $files = glob($mmDir.'*', GLOB_MARK);
                foreach ($files as $file) {
                    if (is_writable($file)){
                      unlink($file);
                    }
                }

                rmdir($mmDir);
            }

            $tmpMmDir = '/tmp/'.$mm->getId().'/';
            if (is_dir($tmpMmDir)){
                $files = glob($tmpMmDir.'*', GLOB_MARK);
                foreach ($files as $file) {
                    if (is_writable($file)){
                      unlink($file);
                    }
                }

                rmdir($tmpMmDir);
            }
        }
    }
}