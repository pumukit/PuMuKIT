<?php

namespace Pumukit\WorkflowBundle\Tests\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Services\TrackService;
use Pumukit\SchemaBundle\EventListener\MultimediaObjectListener;
use Pumukit\WorkflowBundle\EventListener\JobGeneratorListener;

/**
 * @IgnoreAnnotation("dataProvider")
 */
class JobGeneratorListenerTest extends WebTestCase
{
    private $dm;
    private $logger;
    private $listener;
    private $trackDispatcher;
    private $trackService;
    private $jobGeneratorListener;

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->logger = static::$kernel->getContainer()->get('logger');

        $streamserver = array('dir_out' => sys_get_temp_dir());
        $testProfiles = array('video' => array('target' => 'TAGA TAGC', 'resolution_hor' => 0, 'resolution_ver' => 0, 'audio' => false, 'streamserver' => $streamserver),
                              'video2' => array('target' => 'TAGB*, TAGC', 'resolution_hor' => 0, 'resolution_ver' => 0, 'audio' => false, 'streamserver' => $streamserver),
                              'videoSD' => array('target' => 'TAGP, TAGFP*', 'resolution_hor' => 640, 'resolution_ver' => 480, 'audio' => false, 'streamserver' => $streamserver),
                              'videoHD' => array('target' => 'TAGP, TAGFP*', 'resolution_hor' => 1920, 'resolution_ver' => 1024, 'audio' => false, 'streamserver' => $streamserver),
                              'audio' => array('target' => 'TAGA TAGC', 'resolution_hor' => 0, 'resolution_ver' => 0, 'audio' => true, 'streamserver' => $streamserver),
                              'audio2' => array('target' => 'TAGB*, TAGC', 'resolution_hor' => 0, 'resolution_ver' => 0, 'audio' => true, 'streamserver' => $streamserver), );
        $profileService = new ProfileService($testProfiles, $this->dm);

        $jobService = $this->getMockBuilder('Pumukit\EncoderBundle\Services\JobService')
                          ->disableOriginalConstructor()
                          ->getMock();
        $jobService->expects($this->any())
                   ->method('addUniqueJob')
                   ->will($this->returnArgument(1));

        $logger = $this->getMockBuilder('Psr\Log\LoggerInterface')
                       ->disableOriginalConstructor()
                       ->getMock();

        $this->jobGeneratorListener = new JobGeneratorListener($this->dm, $jobService, $profileService, $this->logger);

        $dispatcher = new EventDispatcher();
        $this->listener = new MultimediaObjectListener($this->dm);
        $dispatcher->addListener('multimediaobject.update', array($this->listener, 'postUpdate'));
        $this->trackDispatcher = static::$kernel->getContainer()
          ->get('pumukitschema.track_dispatcher');
        $profileService = new ProfileService($testProfiles, $this->dm);
        $this->trackService = new TrackService($this->dm, $this->trackDispatcher, $profileService, null, true);
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->logger = null;
        $this->jobGeneratorListener = null;
        $this->listener = null;
        $this->trackDispatcher = null;
        $this->trackService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testGetTargets()
    {
        $data = array(
            array('', array('standard' => array(), 'force' => array())),
            array('TAG', array('standard' => array('TAG'), 'force' => array())),
            array('TAG1 TAG2', array('standard' => array('TAG1', 'TAG2'), 'force' => array())),
            array('TAG1, TAG2', array('standard' => array('TAG1', 'TAG2'), 'force' => array())),
            array('TAG1* TAG2*', array('standard' => array(), 'force' => array('TAG1', 'TAG2'))),
            array('TAG1*, TAG2*', array('standard' => array(), 'force' => array('TAG1', 'TAG2'))),
            array('TAG1*, TAG2* TAG3', array('standard' => array('TAG3'), 'force' => array('TAG1', 'TAG2'))),
            array('TAG0 TAG1*, TAG2* TAG3', array('standard' => array('TAG0', 'TAG3'), 'force' => array('TAG1', 'TAG2'))),
            array('TAG0 TAG1**, TAG2* TAG*3', array('standard' => array('TAG0', 'TAG*3'), 'force' => array('TAG1*', 'TAG2'))),
        );
        foreach ($data as $d) {
            $targets = $this->invokeMethod($this->jobGeneratorListener, 'getTargets', array($d[0]));
            $this->assertEquals($d[1], $targets);
        }
    }

    public function testGenerateJobsForSDVideo()
    {
        $track = new Track();
        $track->addTag('master');
        $track->setPath('path');
        $track->setOnlyAudio(false);
        $track->setWidth(640);
        $track->setHeight(480);
        $mmobj = new MultimediaObject();
        $mmobj->addTrack($track);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', array($mmobj, 'TAGA'));
        $this->assertEquals(array('video'), $jobs);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', array($mmobj, 'TAGC'));
        $this->assertEquals(array('video', 'video2'), $jobs);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', array($mmobj, 'TAGB'));
        $this->assertEquals(array('video2', 'audio2'), $jobs);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', array($mmobj, 'TAGP'));
        $this->assertEquals(array('videoSD'), $jobs);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', array($mmobj, 'TAGFP'));
        $this->assertEquals(array('videoSD', 'videoHD'), $jobs);
    }

    public function testGenerateJobsForHDVideo()
    {
        $track = new Track();
        $track->addTag('master');
        $track->setPath('path');
        $track->setOnlyAudio(false);
        $track->setWidth(1280);
        $track->setHeight(720);
        $mmobj = new MultimediaObject();
        $mmobj->addTrack($track);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', array($mmobj, 'TAGA'));
        $this->assertEquals(array('video'), $jobs);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', array($mmobj, 'TAGC'));
        $this->assertEquals(array('video', 'video2'), $jobs);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', array($mmobj, 'TAGB'));
        $this->assertEquals(array('video2', 'audio2'), $jobs);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', array($mmobj, 'TAGP'));
        $this->assertEquals(array('videoHD'), $jobs);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', array($mmobj, 'TAGFP'));
        $this->assertEquals(array('videoSD', 'videoHD'), $jobs);
    }

    public function testGenerateJobsForAudio()
    {
        $track = new Track();
        $track->addTag('master');
        $track->setPath('path');
        $track->setOnlyAudio(true);
        $mmobj = new MultimediaObject();
        $this->trackService->addTrackToMultimediaObject($mmobj, $track, true);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', array($mmobj, 'TAGA'));
        $this->assertEquals(array('audio'), $jobs);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', array($mmobj, 'TAGC'));
        $this->assertEquals(array('audio', 'audio2'), $jobs);

        /* #15818: See commented text in JobGeneratorListener, function generateJobs */
        /* $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', array($mmobj, 'TAGB')); */
        /* $this->assertEquals(array('audio2'), $jobs); //generate a video2 from an audio has no sense. */

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', array($mmobj, 'TAGP'));
        $this->assertEquals(array(), $jobs); //generate a video from an audio has no sense.

        /* #15818: See commented text in JobGeneratorListener, function generateJobs */
        /* $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', array($mmobj, 'TAGFP')); */
        /* $this->assertEquals(array(), $jobs);  //generate a video from an audio has no sense. */
    }

    public function testNotGenerateJobsForPublishedVideo()
    {
        $track = new Track();
        $track->setTags(array('master', 'profile:video'));
        $track->setPath('path');
        $track->setOnlyAudio(false);
        $track->setWidth(640);
        $track->setHeight(480);
        $mmobj = new MultimediaObject();
        $mmobj->addTrack($track);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', array($mmobj, 'TAGC'));
        $this->assertEquals(array(), $jobs);

        //$this->assertEquals(1, 2);
    }

    private function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
