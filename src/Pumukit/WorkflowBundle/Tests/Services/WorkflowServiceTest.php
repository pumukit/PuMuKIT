<?php

namespace Pumukit\WorkflowBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\WorkflowBundle\Services\WorkflowService;


/**
 * @IgnoreAnnotation("dataProvider")
 */
class WorkflowServiceTest extends WebTestCase
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
    }

    public function setUp()
    {

        $streamserver = array('dir_out' => sys_get_temp_dir());
        $testProfiles = array('video' => array('target' => 'TAGA TAGC', 'audio' => false, 'streamserver' => $streamserver),
                              'video2' => array('target' => 'TAGB*, TAGC', 'audio' => false, 'streamserver' => $streamserver),
                              'audio' => array('target' => 'TAGA TAGC', 'audio' => true, 'streamserver' => $streamserver),
                              'audio2' => array('target' => 'TAGB*, TAGC', 'audio' => true, 'streamserver' => $streamserver));
        $profileService = new ProfileService($testProfiles, $this->dm);
      
        $jobService = $this->getMockBuilder('Pumukit\EncoderBundle\Services\JobService')
                          ->disableOriginalConstructor()
                          ->getMock();
        $jobService->expects($this->any())
                   ->method('addUniqueJob')
                   ->will($this->returnArgument(1));

        
        $logger = $this->getMockBuilder('Symfony\Component\HttpKernel\Log\LoggerInterface')
                       ->disableOriginalConstructor()
                       ->getMock();
                
        $this->workflowService = new WorkflowService($this->dm, $jobService, $profileService, $logger);
    }

    
    public function testGetTargets()
    {
        //TODO workaround to solve problems with @dataProvider
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
        foreach($data as $d) {
            $targets = $this->invokeMethod($this->workflowService, 'getTargets', array($d[0]));
            $this->assertEquals($d[1], $targets);
        }
    }


    public function testGenerateJobsForVideo()
    {
        $track = new Track();
        $track->addTag("master");
        $track->setPath("path");
        $track->setOnlyAudio(false);
        $mmobj = new MultimediaObject();
        $mmobj->addTrack($track);

        $jobs = $this->invokeMethod($this->workflowService, 'generateJobs', array($mmobj, 'TAGA'));      
        $this->assertEquals(array('video'), $jobs);

        $jobs = $this->invokeMethod($this->workflowService, 'generateJobs', array($mmobj, 'TAGC'));
        $this->assertEquals(array('video', 'video2'), $jobs);

        $jobs = $this->invokeMethod($this->workflowService, 'generateJobs', array($mmobj, 'TAGB'));
        $this->assertEquals(array('video2', 'audio2'), $jobs);
    }


    public function testGenerateJobsForAudio()
    {
        $track = new Track();
        $track->addTag("master");
        $track->setPath("path");
        $track->setOnlyAudio(true);
        $mmobj = new MultimediaObject();
        $mmobj->addTrack($track);

        $jobs = $this->invokeMethod($this->workflowService, 'generateJobs', array($mmobj, 'TAGA'));
        $this->assertEquals(array('audio'), $jobs);

        $jobs = $this->invokeMethod($this->workflowService, 'generateJobs', array($mmobj, 'TAGC'));
        $this->assertEquals(array('audio', 'audio2'), $jobs);

        $jobs = $this->invokeMethod($this->workflowService, 'generateJobs', array($mmobj, 'TAGB'));
        $this->assertEquals(array('audio2'), $jobs); //generate a video2 from an audio has no sense.
    }



    public function notestGenerateJobsAudioMultimediaObjectGenerateForce()
    {
        $track = new Track();
        $track->addTag("master");
        $track->setPath("path");
        $track->setOnlyAudio(true);
        $mmobj = new MultimediaObject();
        $mmobj->addTrack($track);

        $jobs = $this->invokeMethod($this->workflowService, 'generateJobs', array($mmobj, 'TAGA'));      
        $this->assertEquals(array('audio'), $jobs);
    }

    private function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}