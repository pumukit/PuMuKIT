<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\EventListener;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;

/**
 * @internal
 *
 * @coversNothing
 */
class RemoveListenerTest extends PumukitTestCase
{
    private $repoJobs;
    private $repoMmobj;
    private $repoSeries;
    private $repoUser;
    private $factoryService;
    private $embeddedBroadcastService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        parent::setUp();

        $this->repoJobs = $this->dm->getRepository(Job::class);
        $this->repoMmobj = $this->dm->getRepository(MultimediaObject::class);
        $this->repoSeries = $this->dm->getRepository(Series::class);
        $this->repoUser = $this->dm->getRepository(User::class);
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');
        $this->embeddedBroadcastService = static::$kernel->getContainer()->get('pumukitschema.embeddedbroadcast');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repoJobs = null;
        $this->repoMmobj = null;
        $this->repoSeries = null;
        $this->factoryService = null;
        gc_collect_cycles();
    }

    public function testPreRemove()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->createJobWithStatus(Job::STATUS_FINISHED, $multimediaObject);

        static::assertCount(1, $this->repoSeries->findAll());
        static::assertCount(2, $this->repoMmobj->findAll());
        static::assertCount(1, $this->repoJobs->findAll());

        $this->factoryService->deleteMultimediaObject($multimediaObject);

        static::assertCount(1, $this->repoSeries->findAll());
        static::assertCount(1, $this->repoMmobj->findAll());
        static::assertCount(0, $this->repoJobs->findAll());
    }

    public function testPreRemoveWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Can not delete Multimedia Object with id');
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->createJobWithStatus(Job::STATUS_EXECUTING, $multimediaObject);

        static::assertCount(1, $this->repoSeries->findAll());
        static::assertCount(2, $this->repoMmobj->findAll());
        static::assertCount(1, $this->repoJobs->findAll());

        $this->factoryService->deleteMultimediaObject($multimediaObject);

        static::assertCount(1, $this->repoSeries->findAll());
        static::assertCount(2, $this->repoMmobj->findAll());
        static::assertCount(1, $this->repoJobs->findAll());

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

        static::assertCount(2, $mm1->getGroups());
        static::assertCount(1, $mm2->getGroups());
        static::assertContains($group1, $mm1->getGroups()->toArray());
        static::assertContains($group2, $mm1->getGroups()->toArray());
        static::assertNotContains($group1, $mm2->getGroups()->toArray());
        static::assertContains($group2, $mm2->getGroups()->toArray());

        static::assertCount(2, $embeddedBroadcast1->getGroups());
        static::assertCount(1, $embeddedBroadcast2->getGroups());
        static::assertContains($group1, $embeddedBroadcast1->getGroups()->toArray());
        static::assertContains($group2, $embeddedBroadcast1->getGroups()->toArray());
        static::assertNotContains($group1, $embeddedBroadcast2->getGroups()->toArray());
        static::assertContains($group2, $embeddedBroadcast2->getGroups()->toArray());

        $this->dm->remove($group1);
        $this->dm->flush();

        $mm1 = $this->repoMmobj->find($mm1->getId());
        $mm2 = $this->repoMmobj->find($mm2->getId());

        static::assertCount(1, $mm1->getGroups());
        static::assertCount(1, $mm2->getGroups());
        static::assertNotContains($group1, $mm1->getGroups()->toArray());
        static::assertContains($group2, $mm1->getGroups()->toArray());
        static::assertNotContains($group1, $mm2->getGroups()->toArray());
        static::assertContains($group2, $mm2->getGroups()->toArray());

        static::assertCount(1, $embeddedBroadcast1->getGroups());
        static::assertCount(1, $embeddedBroadcast2->getGroups());
        static::assertNotContains($group1, $embeddedBroadcast1->getGroups()->toArray());
        static::assertContains($group2, $embeddedBroadcast1->getGroups()->toArray());
        static::assertNotContains($group1, $embeddedBroadcast2->getGroups()->toArray());
        static::assertContains($group2, $embeddedBroadcast2->getGroups()->toArray());

        $this->dm->remove($group2);
        $this->dm->flush();

        $mm1 = $this->repoMmobj->find($mm1->getId());
        $mm2 = $this->repoMmobj->find($mm2->getId());

        static::assertCount(0, $mm1->getGroups());
        static::assertCount(0, $mm2->getGroups());
        static::assertNotContains($group1, $mm1->getGroups()->toArray());
        static::assertNotContains($group2, $mm1->getGroups()->toArray());
        static::assertNotContains($group1, $mm2->getGroups()->toArray());
        static::assertNotContains($group2, $mm2->getGroups()->toArray());

        static::assertCount(0, $embeddedBroadcast1->getGroups());
        static::assertCount(0, $embeddedBroadcast2->getGroups());
        static::assertNotContains($group1, $embeddedBroadcast1->getGroups()->toArray());
        static::assertNotContains($group2, $embeddedBroadcast1->getGroups()->toArray());
        static::assertNotContains($group1, $embeddedBroadcast2->getGroups()->toArray());
        static::assertNotContains($group2, $embeddedBroadcast2->getGroups()->toArray());

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
        static::assertCount(2, $user1->getGroups());
        static::assertCount(1, $user2->getGroups());
        static::assertContains($group1, $user1->getGroups()->toArray());
        static::assertContains($group2, $user1->getGroups()->toArray());
        static::assertNotContains($group1, $user2->getGroups()->toArray());
        static::assertContains($group2, $user2->getGroups()->toArray());
        $this->dm->remove($group1);
        $this->dm->flush();
        $user1 = $this->repoUser->find($user1->getId());
        $user2 = $this->repoUser->find($user2->getId());
        static::assertCount(1, $user1->getGroups());
        static::assertCount(1, $user2->getGroups());
        static::assertNotContains($group1, $user1->getGroups()->toArray());
        static::assertContains($group2, $user1->getGroups()->toArray());
        static::assertNotContains($group1, $user2->getGroups()->toArray());
        static::assertContains($group2, $user2->getGroups()->toArray());
        $this->dm->remove($group2);
        $this->dm->flush();
        $user1 = $this->repoUser->find($user1->getId());
        $user2 = $this->repoUser->find($user2->getId());
        static::assertCount(0, $user1->getGroups());
        static::assertCount(0, $user2->getGroups());
        static::assertNotContains($group1, $user1->getGroups()->toArray());
        static::assertNotContains($group2, $user1->getGroups()->toArray());
        static::assertNotContains($group1, $user2->getGroups()->toArray());
        static::assertNotContains($group2, $user2->getGroups()->toArray());
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
