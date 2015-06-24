<?php

namespace Pumukit\EncoderBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\ProfileService;

class ProfileServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $profileService;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
          ->getRepository('PumukitEncoderBundle:Job');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitEncoderBundle:Job')->remove(array());
        $this->dm->flush();

        $this->profileService = new ProfileService($this->getDemoProfiles(), $this->dm);
    }

    public function testGetProfiles()
    {
        $profiles = $this->getDemoProfiles();
        $this->assertEquals(count($profiles), count($this->profileService->getProfiles()));

        $this->assertEquals(0, count($this->profileService->getProfiles(true)));
        $this->assertEquals(0, count($this->profileService->getProfiles(null, false)));
        $this->assertEquals(2, count($this->profileService->getProfiles(null, null, true)));
        $this->assertEquals(2, count($this->profileService->getProfiles(false, true)));
        $this->assertEquals(0, count($this->profileService->getProfiles(null, true, false)));
        $this->assertEquals(0, count($this->profileService->getProfiles(false, null, false)));
        $this->assertEquals(2, count($this->profileService->getProfiles(false, true, true)));
    }

    public function testGetMasterProfiles()
    {
        $profiles = $this->getDemoProfiles();
        $this->assertEquals(count($profiles), count($this->profileService->getMasterProfiles(true)));
        $this->assertEquals(0, count($this->profileService->getMasterProfiles(false)));
    }

    public function testGetProfile()
    {
        $profiles = $this->getDemoProfiles();
        $this->assertEquals($profiles['MASTER_COPY'], $this->profileService->getProfile('MASTER_COPY'));
        $this->assertEquals($profiles['MASTER_VIDEO_H264'], $this->profileService->getProfile('MASTER_VIDEO_H264'));
        $this->assertNull($this->profileService->getProfile('master_COPY')); //Case sensitive
        $this->assertNull($this->profileService->getProfile('master'));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage for dir_out of the streamserver
     */
    public function testInvalidTargetPath()
    {
        $profileService = new ProfileService($this->getDemoProfilesWithNonExistingPath(), $this->dm);
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

    private function getDemoProfilesWithNonExistingPath()
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
                                                                         'dir_out' => '/non/existing/path/storage/masters'
                                                                         ),
                                                 'app' => 'cp',
                                                 'rel_duration_size' => 1,
                                                 'rel_duration_trans' => 1
                                                 )
                          );

        return $profiles;
    }
}