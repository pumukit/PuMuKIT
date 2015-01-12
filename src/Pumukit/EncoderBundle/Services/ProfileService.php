<?php

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\Job;

class ProfileService
{
    private $dm;
    private $repo;

    const STREAMSERVER_STORE = 'Store';
    const STREAMSERVER_DOWNLOAD = 'Download';
    const STREAMSERVER_WMV = 'WMV';
    const STREAMSERVER_FMS = 'FMS';
    const STREAMSERVER_RED5 = 'Red5';
    
    // TODO - Move Profiles to configuration files
    private $profiles = array(
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
                                                     'bat' => 'cp "%1" "%2"',
                                                     'file_cfg' => '??',
                                                     'streamserver' => array(
                                                                             'streamserver_type' => self::STREAMSERVER_STORE,
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
                                                           'bat' => 'BitRate=$(/usr/local/bin/ffprobe "%1" -v 0 -show_format -print_format default=nk=1:nw=1 | sed -n 9p)
                                                                     [[ "$(( BitRate ))" -gt 6000000 ]] && : $(( BitRate = 6000000 ))

                                                                     FrameRate=$(/usr/local/bin/ffprobe "%1" -v 0 -show_streams -select_streams v -print_format default=nk=1:nw=1 | sed -n 18p)

                                                                     BufSize=$(( BitRate*20/FrameRate ))

                                                                     AudioSampleRate=$(/usr/local/bin/ffprobe "%1" -v 0 -show_streams -select_streams a -print_format default=nk=1:nw=1 |sed -n 10p)

                                                                     AudioBitRate=$(/usr/local/bin/ffprobe "%1" -v 0 -show_streams -select_streams a -print_format default=nk=1:nw=1 |sed -n 22p)

                                                                     width=$(/usr/local/bin/ffprobe "%1" -v 0 -show_streams -select_streams v  -print_format default=nk=1:nw=1 |sed -n 9p)

                                                                     [[ "$(( width % 2 ))" -ne 0 ]] && : $(( width += 1 ))

                                                                     height=$(/usr/local/bin/ffprobe "%1" -v 0 -show_streams -select_streams v  -print_format default=nk=1:nw=1 |sed -n 10p)

                                                                     [[ "$(( height % 2 ))" -ne 0 ]] && : $(( height += 1 ))

                                                                     /usr/local/bin/ffmpeg -y -i "%1" -acodec libfdk_aac -b:a $AudioBitRate -ac 2 -ar $AudioSampleRate -vcodec libx264 -r 25 -preset slow -crf 15 -maxrate $BitRate -bufsize $BufSize -s $width"x"$height -threads 0 "%2"',
                                                           'file_cfg' => '',
                                                           'streamserver' => array(
                                                                                   'streamserver_type' => self::STREAMSERVER_STORE,
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

    /**
     * Constructor
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitEncoderBundle:Job');
    }

    /**
     * Get available profiles
     */
    public function getProfiles()
    {
        return $this->profiles;
    }

    /**
     * Get given profile
     */
    public function getProfile($profile)
    {
        if (isset($this->profiles[strtoupper($profile)])){
            return $this->profiles[strtoupper($profile)];
        }

      return null;      
    }
}