<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\ProfileService;

/**
 * @internal
 * @coversNothing
 */
class ProfileServiceTest extends PumukitTestCase
{
    private $repo;
    private $profileService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(Job::class);
        $this->profileService = new ProfileService($this->getDemoProfiles(), $this->dm);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->dm->close();

        $this->repo = null;
        $this->profileService = null;
        gc_collect_cycles();
    }

    public function testGetProfiles()
    {
        $profiles = $this->getDemoProfiles();
        static::assertCount(count($profiles), $this->profileService->getProfiles());

        static::assertCount(0, $this->profileService->getProfiles(true));
        static::assertCount(0, $this->profileService->getProfiles(null, false));
        static::assertCount(2, $this->profileService->getProfiles(null, null, true));
        static::assertCount(2, $this->profileService->getProfiles(false, true));
        static::assertCount(0, $this->profileService->getProfiles(null, true, false));
        static::assertCount(0, $this->profileService->getProfiles(false, null, false));
        static::assertCount(2, $this->profileService->getProfiles(false, true, true));

        static::assertCount(2, $this->profileService->getProfilesByTags([]));
        static::assertCount(2, $this->profileService->getProfilesByTags('uno'));
        static::assertCount(1, $this->profileService->getProfilesByTags(['tres']));
        static::assertCount(1, $this->profileService->getProfilesByTags(['uno', 'tres']));
    }

    public function testGetMasterProfiles()
    {
        $profiles = $this->getDemoProfiles();
        static::assertCount(count($profiles), $this->profileService->getMasterProfiles(true));
        static::assertCount(0, $this->profileService->getMasterProfiles(false));
    }

    public function testGetDefaultMasterProfile()
    {
        $profileService = new ProfileService($this->getDemoProfiles(), $this->dm);
        static::assertEquals('MASTER_VIDEO_H264', $profileService->getDefaultMasterProfile());

        $profiles = ['MASTER_COPY' => $this->getDemoProfiles()['MASTER_COPY']];
        $profileService = new ProfileService($profiles, $this->dm);
        static::assertEquals('MASTER_COPY', $profileService->getDefaultMasterProfile());

        $profile = $this->getDemoProfiles()['MASTER_VIDEO_H264'];
        $profile['master'] = false;
        $profiles = ['VIDEO_H264' => $profile];
        $profileService = new ProfileService($profiles, $this->dm);
        static::assertNull($profileService->getDefaultMasterProfile());

        $profileService = new ProfileService([], $this->dm);
        static::assertNull($profileService->getDefaultMasterProfile());
    }

    public function testGetProfile()
    {
        $profiles = $this->getDemoProfiles();
        static::assertEquals($profiles['MASTER_COPY'], $this->profileService->getProfile('MASTER_COPY'));
        static::assertEquals($profiles['MASTER_VIDEO_H264'], $this->profileService->getProfile('MASTER_VIDEO_H264'));
        static::assertNull($this->profileService->getProfile('master_COPY')); //Case sensitive
        static::assertNull($this->profileService->getProfile('master'));
    }

    public function testInvalidTargetPath()
    {
$this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('for dir_out of the streamserver');
        //        $profileService = new ProfileService($this->getDemoProfilesWithNonExistingPath(), $this->dm);
        ProfileService::validateProfilesDir($this->getDemoProfilesWithNonExistingPath());
    }

    private function getDemoProfiles()
    {
        return [
            'MASTER_COPY' => [
                'display' => false,
                'wizard' => true,
                'master' => true,
                'tags' => 'uno,dos tres, copy',
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
                    'dir_out' => __DIR__.'/../Resources/dir_out',                                                         ],
                'app' => 'cp',
                'rel_duration_size' => 1,
                'rel_duration_trans' => 1,
            ],
            'MASTER_VIDEO_H264' => [
                'display' => false,
                'wizard' => true,
                'master' => true,
                'tags' => 'uno',
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

    private function getDemoProfilesWithNonExistingPath()
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
                    'dir_out' => '/non/existing/path/storage/masters',
                ],
                'app' => 'cp',
                'rel_duration_size' => 1,
                'rel_duration_trans' => 1,
            ],
        ];
    }
}
