<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\TrackService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\EncoderBundle\Services\CpuService;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Document\Job;

class TrackServiceTest extends WebTestCase
{
    private $dm;
    private $repoJobs;
    private $repoMmobj;
    private $trackService;
    private $factoryService;
    private $resourcesDir;
    private $logger;
    private $tokenStorage;

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
        $this->dm->getDocumentCollection('PumukitSchemaBundle:SeriesType')
          ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')
          ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Broadcast')
          ->remove(array());
        $this->dm->getDocumentCollection('PumukitEncoderBundle:Job')
          ->remove(array());
        $this->dm->flush();
        
        $profileService = new ProfileService($this->getDemoProfiles(), $this->dm);
        $cpuService = new CpuService($this->getDemoCpus(), $this->dm);
        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $inspectionService = $this->getMock('Pumukit\InspectionBundle\Services\InspectionServiceInterface');
        $inspectionService->expects($this->any())->method('getDuration')->will($this->returnValue(5));
        $jobService = new JobService($this->dm, $profileService, $cpuService, 
                                     $inspectionService, $dispatcher, $this->logger, 
                                     $this->tokenStorage, "test", null);
        $this->trackService = new TrackService($this->dm, $jobService, $profileService, null, true);

        $this->tmpDir = $this->trackService->getTempDirs()[0];
    }

    public function testCreateTrackFromLocalHardDrive()
    {
        $this->createBroadcasts();

        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getTracks()));
        $this->assertEquals(0, count($this->repoJobs->findAll()));

        $originalFile = $this->resourcesDir.'/camera.mp4';

        $filePath = $this->resourcesDir.'/cameraCopy.mp4';
        if (copy($originalFile, $filePath)){
          $file = new UploadedFile($filePath, 'camera.mp4', null, null, null, true);
          
          $profile = 'MASTER_COPY';
          $priority = 2;
          $language = 'en';
          $description = array(
                                    'en' => 'local track description',
                                    'es' => 'descripción del archivo local',
                                    );
          
          $multimediaObject = $this->trackService->createTrackFromLocalHardDrive($multimediaObject, $file, $profile, $priority, $language, $description);
          
          $this->assertEquals(0, count($multimediaObject->getTracks()));
          $this->assertEquals(1, count($this->repoJobs->findAll()));
        }

        $this->deleteCreatedFiles();
    }

    public function testCreateTrackFromInboxOnServer()
    {
        $this->createBroadcasts();

        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getTracks()));
        $this->assertEquals(0, count($this->repoJobs->findAll()));

        $originalFile = $this->resourcesDir.DIRECTORY_SEPARATOR.'camera.mp4';

        $filePath = $this->resourcesDir.DIRECTORY_SEPARATOR.'cameraCopy.mp4';
        if (copy($originalFile, $filePath)){
          $profile = 'MASTER_COPY';
          $priority = 2;
          $language = 'en';
          $description = array(
                               'en' => 'track description inbox',
                               'es' => 'descripción del archivo inbox',
                               );
          
          $multimediaObject = $this->trackService->createTrackFromInboxOnServer($multimediaObject, $filePath, $profile, $priority, $language, $description);
          
          $this->assertEquals(0, count($multimediaObject->getTracks()));
          $this->assertEquals(1, count($this->repoJobs->findAll()));
        }

        $this->deleteCreatedFiles();
        unlink($filePath);
    }

    public function testUpdateTrackInMultimediaObject()
    {
        $this->createBroadcasts();

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

        $this->trackService->updateTrackInMultimediaObject($multimediaObject);
        $multimediaObject = $this->repoMmobj->find($multimediaObject->getId());
        $track = $multimediaObject->getTracks()[0];
        $this->assertEquals($newUrl, $track->getUrl());
    }

    public function testRemoveTrackFromMultimediaObject()
    {
        $this->createBroadcasts();

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
        $this->createBroadcasts();

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

        $arrayTracks = array($track1, $track2, $track3, $track4, $track5);
        $this->assertEquals($arrayTracks, $multimediaObject->getTracks()->toArray());

        $multimediaObject = $this->trackService->upTrackInMultimediaObject($multimediaObject, $track3->getId());
        $multimediaObject = $this->repoMmobj->find($multimediaObject->getId());

        $arrayTracks = array($track1, $track3, $track2, $track4, $track5);
        $this->assertEquals($arrayTracks, $multimediaObject->getTracks()->toArray());

        $multimediaObject = $this->trackService->downTrackInMultimediaObject($multimediaObject, $track4->getId());
        $multimediaObject = $this->repoMmobj->find($multimediaObject->getId());

        $arrayTracks = array($track1, $track3, $track2, $track5, $track4);
        $this->assertEquals($arrayTracks, $multimediaObject->getTracks()->toArray());
    }

    private function createBroadcasts()
    {
        $locale = 'en';

        $broadcastPrivate = new Broadcast();
        $broadcastPrivate->setLocale($locale);
        $broadcastPrivate->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PRI);
        $broadcastPrivate->setDefaultSel(true);
        $broadcastPrivate->setName('Private');

        $broadcastPublic = new Broadcast();
        $broadcastPublic->setLocale($locale);
        $broadcastPublic->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcastPublic->setDefaultSel(false);
        $broadcastPublic->setName('Public');

        $broadcastCorporative = new Broadcast();
        $broadcastCorporative->setLocale($locale);
        $broadcastCorporative->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_COR);
        $broadcastCorporative->setDefaultSel(false);
        $broadcastCorporative->setName('Corporative');

        $this->dm->persist($broadcastPrivate);
        $this->dm->persist($broadcastPublic);
        $this->dm->persist($broadcastCorporative);
        $this->dm->flush();
    }

    private function createFormData($number)
    {
        $formData = array(
                          'profile' => 'MASTER_COPY',
                          'priority' => 2,
                          'language' => 'en',
                          'i18n_description' => array(
                                                      'en' => 'track description '.$number,
                                                      'es' => 'descripción del archivo '.$number,
                                                      ),
                          );

        return $formData;
    }

    private function getDemoCpus()
    {
        $cpus = array(
                      'CPU_LOCAL' => array(
                                           'id' => 1,
                                           'host' => '127.0.0.1',
                                           'max' => 1,
                                           'number' => 1,
                                           'type' => CpuService::TYPE_LINUX,
                                           'user' => 'transco1',
                                           'password' => 'PUMUKIT',
                                           'description' => 'Pumukit transcoder'
                                           ),
                      'CPU_REMOTE' => array(
                                            'id' => 2,
                                            'host' => '192.168.5.123',
                                            'max' => 2,
                                            'number' => 1,
                                            'type' => CpuService::TYPE_LINUX,
                                            'user' => 'transco2',
                                            'password' => 'PUMUKIT',
                                            'description' => 'Pumukit transcoder'
                                            )
                      );
        
        return $cpus;
    }

    private function getDemoProfiles()
    {
        $profiles = array(
                          'MASTER_COPY' => array(
                                                 'display' => false,
                                                 'wizard' => true,
                                                 'master' => true,
                                                 'resolution_hor' => 0,
                                                 'resolution_ver' => 0,
                                                 'framerate' => '0',
                                                 'channels' => 1,
                                                 'audio' => false,
                                                 'bat' => 'cp "{{input}}" "{{output}}"',
                                                 'streamserver' => array(
                                                                         'type' => ProfileService::STREAMSERVER_STORE,
                                                                         'host' => '127.0.0.1',
                                                                         'name' => 'Localmaster',
                                                                         'description' => 'Local masters server',
                                                                         'dir_out' => __DIR__.'/../Resources/dir_out'                                                         ),
                                                 'app' => 'cp',
                                                 'rel_duration_size' => 1,
                                                 'rel_duration_trans' => 1
                                                 ),
                          'MASTER_VIDEO_H264' => array(
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
                                                       'bat' => 'avconv -y -i "{{input}}" -acodec libvo_aacenc -vcodec libx264 -preset slow -crf 15 -threads 0 "{{output}}"',
                                                       'streamserver' => array(
                                                                               'type' => ProfileService::STREAMSERVER_STORE,
                                                                               'host' => '192.168.5.125',
                                                                               'name' => 'Download',
                                                                               'description' => 'Download server',
                                                                               'dir_out' => __DIR__.'/../Resources/dir_out',
                                                                               'url_out' => 'http://localhost:8000/downloads/'
                                                                               ),
                                                       'app' => 'avconv',
                                                       'rel_duration_size' => 1,
                                                       'rel_duration_trans' => 1
                                                       )
                          );

        return $profiles;
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
