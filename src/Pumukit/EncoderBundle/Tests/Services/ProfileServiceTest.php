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

    private function getDemoProfiles()
    {
        $profiles = array(
                          'MASTER_COPY' => array(
                                                 'id' => 1,
                                                 'name' => 'master_copy',
                                                 'rank' => 1,
                                                 'display' => false,
                                                 'wizard' => true,
                                                 'master' => true,
                                                 'format' => '???',
                                                 'codec' => '??',
                                                 'mime_type' => '??',
                                                 'extension' => '???',
                                                 'resolution_hor' => 0,
                                                 'resolution_ver' => 0,
                                                 'bitrate' => '??',
                                                 'framerate' => 0,
                                                 'channels' => 1,
                                                 'audio' => false,
                                                 'bat' => 'cp "{{input}}" "{{output}}"',
                                                 'file_cfg' => '??',
                                                 'streamserver' => array(
                                                                         'streamserver_type' => ProfileService::STREAMSERVER_STORE,
                                                                         'ip' => '127.0.0.1',
                                                                         'name' => 'Localmaster',
                                                                         'description' => 'Local masters server',
                                                                         'dir_out' => '/mnt/nas/storage/masters',
                                                                         'url_out' => ''
                                                                         ),
                                                 'app' => 'cp',
                                                 'rel_duration_size' => 1,
                                                 'rel_duration_trans' => 1,
                                                 'prescript' => '?????'
                                                 ),
                          'MASTER_VIDEO_H264' => array(
                                                       'id' => 2,
                                                       'name' => 'master_video_h264',
                                                       'rank' => 2,
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
                                                       'framerate' => 25,
                                                       'channels' => 1,
                                                       'audio' => false,
                                                       'bat' => 'BitRate=$(/usr/local/bin/ffprobe "{{input}}" -v 0 -show_format -print_format default=nk=1:nw=1 | sed -n 9p)
                                                                     [[ "$(( BitRate ))" -gt 6000000 ]] && : $(( BitRate = 6000000 ))

                                                                     FrameRate=$(/usr/local/bin/ffprobe "{{input}}" -v 0 -show_streams -select_streams v -print_format default=nk=1:nw=1 | sed -n 18p)

                                                                     BufSize=$(( BitRate*20/FrameRate ))

                                                                     AudioSampleRate=$(/usr/local/bin/ffprobe "{{input}}" -v 0 -show_streams -select_streams a -print_format default=nk=1:nw=1 |sed -n 10p)

                                                                     AudioBitRate=$(/usr/local/bin/ffprobe "{{input}}" -v 0 -show_streams -select_streams a -print_format default=nk=1:nw=1 |sed -n 22p)

                                                                     width=$(/usr/local/bin/ffprobe "{{input}}" -v 0 -show_streams -select_streams v  -print_format default=nk=1:nw=1 |sed -n 9p)

                                                                     [[ "$(( width % 2 ))" -ne 0 ]] && : $(( width += 1 ))

                                                                     height=$(/usr/local/bin/ffprobe "{{input}}" -v 0 -show_streams -select_streams v  -print_format default=nk=1:nw=1 |sed -n 10p)

                                                                     [[ "$(( height % 2 ))" -ne 0 ]] && : $(( height += 1 ))

                                                                     /usr/local/bin/ffmpeg -y -i "{{input}}" -acodec libfdk_aac -b:a $AudioBitRate -ac 2 -ar $AudioSampleRate -vcodec libx264 -r 25 -preset slow -crf 15 -maxrate $BitRate -bufsize $BufSize -s $width"x"$height -threads 0 "{{output}}"',
                                                       'file_cfg' => '',
                                                       'streamserver' => array(
                                                                               'streamserver_type' => ProfileService::STREAMSERVER_STORE,
                                                                               'ip' => '192.168.5.125',
                                                                               'name' => 'Download',
                                                                               'description' => 'Download server',
                                                                               'dir_out' => '/mnt/nas/storage/downloads',
                                                                               'url_out' => 'http://localhost:8000/downloads/'
                                                                               ),
                                                       'app' => 'ffmpeg',
                                                       'rel_duration_size' => 1,
                                                       'rel_duration_trans' => 1,
                                                       'prescript' => '?????'
                                                       )
                          );

        return $profiles;
    }
}