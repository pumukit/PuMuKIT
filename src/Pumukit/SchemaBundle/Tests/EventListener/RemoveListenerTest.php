<?php

namespace Pumukit\SchemaBundle\Tests\EventListener;

use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class RemoveListenerTest extends WebTestCase
{
    private $dm;
    private $repoJobs;
    private $repoMmobj;
    private $repoSeries;
    private $repoUser;
    private $factoryService;
    private $resourcesDir;
    private $embeddedBroadcastService;
    private $logger;
    private $tokenStorage;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->logger = static::$kernel->getContainer()->get('logger');
        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repoJobs = $this->dm->getRepository(Job::class);
        $this->repoMmobj = $this->dm->getRepository(MultimediaObject::class);
        $this->repoSeries = $this->dm->getRepository(Series::class);
        $this->repoUser = $this->dm->getRepository(User::class);
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');
        $this->embeddedBroadcastService = static::$kernel->getContainer()
            ->get('pumukitschema.embeddedbroadcast')
        ;
        $this->tokenStorage = static::$kernel->getContainer()->get('security.token_storage');

        $this->resourcesDir = realpath(__DIR__.'/../Resources');

        $this->dm->getDocumentCollection(MultimediaObject::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection(Series::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection(Group::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection(Job::class)
            ->remove([])
        ;
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->logger = null;
        $this->dm = null;
        $this->repoJobs = null;
        $this->repoMmobj = null;
        $this->repoSeries = null;
        $this->factoryService = null;
        $this->tokenStorage = null;
        $this->resourcesDir = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testPreRemove()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->createJobWithStatus(Job::STATUS_FINISHED, $multimediaObject);

        $this->assertEquals(1, count($this->repoSeries->findAll()));
        $this->assertEquals(2, count($this->repoMmobj->findAll()));
        $this->assertEquals(1, count($this->repoJobs->findAll()));

        $this->factoryService->deleteMultimediaObject($multimediaObject);

        $this->assertEquals(1, count($this->repoSeries->findAll()));
        $this->assertEquals(1, count($this->repoMmobj->findAll()));
        $this->assertEquals(0, count($this->repoJobs->findAll()));
    }

    /**
     * @expectedException \Exception
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

        $this->factoryService->deleteMultimediaObject($multimediaObject);

        $this->assertEquals(1, count($this->repoSeries->findAll()));
        $this->assertEquals(2, count($this->repoMmobj->findAll()));
        $this->assertEquals(1, count($this->repoJobs->findAll()));

        $this->deleteCreatedFiles();
    }

    public function testPreRemoveGroup()
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $series = $this->factoryService->createSeries();

        $this->dm->persist($series);
        $this->dm->flush();

        $mm1 = $this->factoryService->createMultimediaObject($series);
        $mm2 = $this->factoryService->createMultimediaObject($series);

        $mm1->addGroup($group1);
        $mm1->addGroup($group2);
        $mm2->addGroup($group2);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->flush();

        $this->embeddedBroadcastService->updateTypeAndName(EmbeddedBroadcast::TYPE_GROUPS, $mm1, false);
        $this->embeddedBroadcastService->updateTypeAndName(EmbeddedBroadcast::TYPE_GROUPS, $mm2, false);
        $this->embeddedBroadcastService->addGroup($group1, $mm1, false);
        $this->embeddedBroadcastService->addGroup($group2, $mm1, false);
        $this->embeddedBroadcastService->addGroup($group2, $mm2, false);
        $this->dm->flush();

        $embeddedBroadcast1 = $mm1->getEmbeddedBroadcast();
        $embeddedBroadcast2 = $mm2->getEmbeddedBroadcast();

        $this->assertEquals(2, count($mm1->getGroups()));
        $this->assertEquals(1, count($mm2->getGroups()));
        $this->assertTrue(in_array($group1, $mm1->getGroups()->toArray()));
        $this->assertTrue(in_array($group2, $mm1->getGroups()->toArray()));
        $this->assertFalse(in_array($group1, $mm2->getGroups()->toArray()));
        $this->assertTrue(in_array($group2, $mm2->getGroups()->toArray()));

        $this->assertEquals(2, count($embeddedBroadcast1->getGroups()));
        $this->assertEquals(1, count($embeddedBroadcast2->getGroups()));
        $this->assertTrue(in_array($group1, $embeddedBroadcast1->getGroups()->toArray()));
        $this->assertTrue(in_array($group2, $embeddedBroadcast1->getGroups()->toArray()));
        $this->assertFalse(in_array($group1, $embeddedBroadcast2->getGroups()->toArray()));
        $this->assertTrue(in_array($group2, $embeddedBroadcast2->getGroups()->toArray()));

        $this->dm->remove($group1);
        $this->dm->flush();

        $mm1 = $this->repoMmobj->find($mm1->getId());
        $mm2 = $this->repoMmobj->find($mm2->getId());

        $this->assertEquals(1, count($mm1->getGroups()));
        $this->assertEquals(1, count($mm2->getGroups()));
        $this->assertFalse(in_array($group1, $mm1->getGroups()->toArray()));
        $this->assertTrue(in_array($group2, $mm1->getGroups()->toArray()));
        $this->assertFalse(in_array($group1, $mm2->getGroups()->toArray()));
        $this->assertTrue(in_array($group2, $mm2->getGroups()->toArray()));

        $this->assertEquals(1, count($embeddedBroadcast1->getGroups()));
        $this->assertEquals(1, count($embeddedBroadcast2->getGroups()));
        $this->assertFalse(in_array($group1, $embeddedBroadcast1->getGroups()->toArray()));
        $this->assertTrue(in_array($group2, $embeddedBroadcast1->getGroups()->toArray()));
        $this->assertFalse(in_array($group1, $embeddedBroadcast2->getGroups()->toArray()));
        $this->assertTrue(in_array($group2, $embeddedBroadcast2->getGroups()->toArray()));

        $this->dm->remove($group2);
        $this->dm->flush();

        $mm1 = $this->repoMmobj->find($mm1->getId());
        $mm2 = $this->repoMmobj->find($mm2->getId());

        $this->assertEquals(0, count($mm1->getGroups()));
        $this->assertEquals(0, count($mm2->getGroups()));
        $this->assertFalse(in_array($group1, $mm1->getGroups()->toArray()));
        $this->assertFalse(in_array($group2, $mm1->getGroups()->toArray()));
        $this->assertFalse(in_array($group1, $mm2->getGroups()->toArray()));
        $this->assertFalse(in_array($group2, $mm2->getGroups()->toArray()));

        $this->assertEquals(0, count($embeddedBroadcast1->getGroups()));
        $this->assertEquals(0, count($embeddedBroadcast2->getGroups()));
        $this->assertFalse(in_array($group1, $embeddedBroadcast1->getGroups()->toArray()));
        $this->assertFalse(in_array($group2, $embeddedBroadcast1->getGroups()->toArray()));
        $this->assertFalse(in_array($group1, $embeddedBroadcast2->getGroups()->toArray()));
        $this->assertFalse(in_array($group2, $embeddedBroadcast2->getGroups()->toArray()));

        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $user1 = $this->createUser('1');
        $user2 = $this->createUser('2');
        $user1->addGroup($group1);
        $user1->addGroup($group2);
        $user2->addGroup($group2);
        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();
        $this->assertEquals(2, count($user1->getGroups()));
        $this->assertEquals(1, count($user2->getGroups()));
        $this->assertTrue(in_array($group1, $user1->getGroups()->toArray()));
        $this->assertTrue(in_array($group2, $user1->getGroups()->toArray()));
        $this->assertFalse(in_array($group1, $user2->getGroups()->toArray()));
        $this->assertTrue(in_array($group2, $user2->getGroups()->toArray()));
        $this->dm->remove($group1);
        $this->dm->flush();
        $user1 = $this->repoUser->find($user1->getId());
        $user2 = $this->repoUser->find($user2->getId());
        $this->assertEquals(1, count($user1->getGroups()));
        $this->assertEquals(1, count($user2->getGroups()));
        $this->assertFalse(in_array($group1, $user1->getGroups()->toArray()));
        $this->assertTrue(in_array($group2, $user1->getGroups()->toArray()));
        $this->assertFalse(in_array($group1, $user2->getGroups()->toArray()));
        $this->assertTrue(in_array($group2, $user2->getGroups()->toArray()));
        $this->dm->remove($group2);
        $this->dm->flush();
        $user1 = $this->repoUser->find($user1->getId());
        $user2 = $this->repoUser->find($user2->getId());
        $this->assertEquals(0, count($user1->getGroups()));
        $this->assertEquals(0, count($user2->getGroups()));
        $this->assertFalse(in_array($group1, $user1->getGroups()->toArray()));
        $this->assertFalse(in_array($group2, $user1->getGroups()->toArray()));
        $this->assertFalse(in_array($group1, $user2->getGroups()->toArray()));
        $this->assertFalse(in_array($group2, $user2->getGroups()->toArray()));
    }

    private function createJobWithStatus($status, $multimediaObject)
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

        foreach ($mmobjs as $mm) {
            $mmDir = $this->getDemoProfiles()['MASTER_COPY']['streamserver']['dir_out'].'/'.$mm->getSeries()->getId().'/';
            if (is_dir($mmDir)) {
                $files = glob($mmDir.'*', GLOB_MARK);
                foreach ($files as $file) {
                    if (is_writable($file)) {
                        unlink($file);
                    }
                }

                rmdir($mmDir);
            }

            $tmpMmDir = '/tmp/'.$mm->getId().'/';
            if (is_dir($tmpMmDir)) {
                $files = glob($tmpMmDir.'*', GLOB_MARK);
                foreach ($files as $file) {
                    if (is_writable($file)) {
                        unlink($file);
                    }
                }

                rmdir($tmpMmDir);
            }
        }
    }

    private function createGroup($key = 'Group1', $name = 'Group 1')
    {
        $group = new Group();
        $group->setKey($key);
        $group->setName($name);
        $this->dm->persist($group);
        $this->dm->flush();

        return $group;
    }

    private function createUser($number)
    {
        $username = 'username'.$number;
        $email = 'user'.$number.'@mail.com';
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $this->dm->persist($user);
        $this->dm->flush();

        return $user;
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
