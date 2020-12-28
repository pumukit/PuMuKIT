<?php

declare(strict_types=1);

namespace Pumukit\WorkflowBundle\Tests\EventListener;

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\EventListener\MultimediaObjectListener;
use Pumukit\SchemaBundle\Services\TextIndexService;
use Pumukit\SchemaBundle\Services\TrackService;
use Pumukit\WorkflowBundle\EventListener\JobGeneratorListener;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @IgnoreAnnotation("dataProvider")
 *
 * @internal
 * @coversNothing
 */
class JobGeneratorListenerTest extends PumukitTestCase
{
    private $logger;
    private $listener;
    private $trackDispatcher;
    private $trackService;
    private $jobGeneratorListener;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        parent::setUp();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $streamserver = ['dir_out' => sys_get_temp_dir()];
        $testProfiles = ['video' => ['target' => 'TAGA TAGC', 'resolution_hor' => 0, 'resolution_ver' => 0, 'audio' => false, 'streamserver' => $streamserver],
            'video2' => ['target' => 'TAGB*, TAGC', 'resolution_hor' => 0, 'resolution_ver' => 0, 'audio' => false, 'streamserver' => $streamserver],
            'videoSD' => ['target' => 'TAGP, TAGFP*', 'resolution_hor' => 640, 'resolution_ver' => 480, 'audio' => false, 'streamserver' => $streamserver],
            'videoHD' => ['target' => 'TAGP, TAGFP*', 'resolution_hor' => 1920, 'resolution_ver' => 1024, 'audio' => false, 'streamserver' => $streamserver],
            'audio' => ['target' => 'TAGA TAGC', 'resolution_hor' => 0, 'resolution_ver' => 0, 'audio' => true, 'streamserver' => $streamserver],
            'audio2' => ['target' => 'TAGB*, TAGC', 'resolution_hor' => 0, 'resolution_ver' => 0, 'audio' => true, 'streamserver' => $streamserver], ];
        $profileService = new ProfileService($testProfiles, $this->dm);

        $jobService = $this->getMockBuilder('Pumukit\EncoderBundle\Services\JobService')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $jobService->expects(static::any())
            ->method('addUniqueJob')
            ->will(static::returnArgument(1))
        ;

        $this->jobGeneratorListener = new JobGeneratorListener($this->dm, $jobService, $profileService, $this->logger);

        $dispatcher = new EventDispatcher();
        $this->listener = new MultimediaObjectListener($this->dm, new TextIndexService());
        $dispatcher->addListener('multimediaobject.update', [$this->listener, 'postUpdate']);
        $this->trackDispatcher = static::$kernel->getContainer()->get('pumukitschema.track_dispatcher');
        $profileService = new ProfileService($testProfiles, $this->dm);
        $this->trackService = new TrackService($this->dm, $this->trackDispatcher, null, true);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->logger = null;
        $this->jobGeneratorListener = null;
        $this->listener = null;
        $this->trackDispatcher = null;
        $this->trackService = null;
        gc_collect_cycles();
    }

    public function testGetTargets()
    {
        $data = [
            ['', ['standard' => [], 'force' => []]],
            ['TAG', ['standard' => ['TAG'], 'force' => []]],
            ['TAG1 TAG2', ['standard' => ['TAG1', 'TAG2'], 'force' => []]],
            ['TAG1, TAG2', ['standard' => ['TAG1', 'TAG2'], 'force' => []]],
            ['TAG1* TAG2*', ['standard' => [], 'force' => ['TAG1', 'TAG2']]],
            ['TAG1*, TAG2*', ['standard' => [], 'force' => ['TAG1', 'TAG2']]],
            ['TAG1*, TAG2* TAG3', ['standard' => ['TAG3'], 'force' => ['TAG1', 'TAG2']]],
            ['TAG0 TAG1*, TAG2* TAG3', ['standard' => ['TAG0', 'TAG3'], 'force' => ['TAG1', 'TAG2']]],
            ['TAG0 TAG1**, TAG2* TAG*3', ['standard' => ['TAG0', 'TAG*3'], 'force' => ['TAG1*', 'TAG2']]],
        ];
        foreach ($data as $d) {
            $targets = $this->invokeMethod($this->jobGeneratorListener, 'getTargets', [$d[0]]);
            static::assertEquals($d[1], $targets);
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

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', [$mmobj, 'TAGA']);
        static::assertEquals(['video'], $jobs);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', [$mmobj, 'TAGC']);
        static::assertEquals(['video', 'video2'], $jobs);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', [$mmobj, 'TAGB']);
        static::assertEquals(['video2', 'audio2'], $jobs);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', [$mmobj, 'TAGP']);
        static::assertEquals(['videoSD'], $jobs);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', [$mmobj, 'TAGFP']);
        static::assertEquals(['videoSD', 'videoHD'], $jobs);
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

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', [$mmobj, 'TAGA']);
        static::assertEquals(['video'], $jobs);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', [$mmobj, 'TAGC']);
        static::assertEquals(['video', 'video2'], $jobs);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', [$mmobj, 'TAGB']);
        static::assertEquals(['video2', 'audio2'], $jobs);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', [$mmobj, 'TAGP']);
        static::assertEquals(['videoHD'], $jobs);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', [$mmobj, 'TAGFP']);
        static::assertEquals(['videoSD', 'videoHD'], $jobs);
    }

    public function testGenerateJobsForAudio()
    {
        $track = new Track();
        $track->addTag('master');
        $track->setPath('path');
        $track->setOnlyAudio(true);
        $mmobj = new MultimediaObject();
        $this->trackService->addTrackToMultimediaObject($mmobj, $track, true);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', [$mmobj, 'TAGA']);
        static::assertEquals(['audio'], $jobs);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', [$mmobj, 'TAGC']);
        static::assertEquals(['audio', 'audio2'], $jobs);

        // #15818: See commented text in JobGeneratorListener, function generateJobs
        // $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', array($mmobj, 'TAGB'));
        // $this->assertEquals(array('audio2'), $jobs); //generate a video2 from an audio has no sense.

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', [$mmobj, 'TAGP']);
        static::assertEquals([], $jobs); //generate a video from an audio has no sense.

        // #15818: See commented text in JobGeneratorListener, function generateJobs
        // $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', array($mmobj, 'TAGFP'));
        // $this->assertEquals(array(), $jobs);  //generate a video from an audio has no sense.
    }

    public function testNotGenerateJobsForPublishedVideo()
    {
        $track = new Track();
        $track->setTags(['master', 'profile:video']);
        $track->setPath('path');
        $track->setOnlyAudio(false);
        $track->setWidth(640);
        $track->setHeight(480);
        $mmobj = new MultimediaObject();
        $mmobj->addTrack($track);

        $jobs = $this->invokeMethod($this->jobGeneratorListener, 'generateJobs', [$mmobj, 'TAGC']);
        static::assertEquals([], $jobs);

        //$this->assertEquals(1, 2);
    }

    private function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
