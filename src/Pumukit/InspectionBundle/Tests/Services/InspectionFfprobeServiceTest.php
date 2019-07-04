<?php

namespace Pumukit\InspectionBundle\Tests\Services;

use PHPUnit\Framework\TestCase;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;
use Pumukit\InspectionBundle\Utils\TestCommand;
use Pumukit\SchemaBundle\Document\Track;

/**
 * @internal
 * @coversNothing
 */
class InspectionFfprobeServiceTest extends TestCase
{
    private $resources_dir;
    private $wrong_file_textfile;
    private $wrong_file_zerofile;
    private $wrong_file_subtitle;
    private $vid_no_audio;

    public function setUp()
    {
        if (false === TestCommand::commandExists('ffprobe')) {
            $this->markTestSkipped('FFprobe test marks skipped (No ffprobe command).');
        }

        $this->resources_dir = realpath(__DIR__.'/../Resources').DIRECTORY_SEPARATOR;
        $this->wrong_file_textfile = $this->resources_dir.'textfile.txt';
        $this->wrong_file_zerofile = $this->resources_dir.'zerosizefile.txt';
        $this->wrong_file_subtitle = $this->resources_dir.'subtitle.vtt';
        $this->vid_no_audio = $this->resources_dir.'SCREEN.mp4';
    }

    public function tearDown()
    {
        $this->resources_dir = null;
        $this->wrong_file_textfile = null;
        $this->wrong_file_zerofile = null;
        $this->wrong_file_subtitle = null;
        $this->vid_no_audio = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testGetDurationFileNotExists()
    {
        $is = new InspectionFfprobeService();
        $is->getDuration('http://trololo.com');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetDurationFileWithoutMultimediaContent()
    {
        $is = new InspectionFfprobeService();
        $is->getDuration($this->wrong_file_textfile);
        $is = new InspectionFfprobeService();
        $is->getDuration($this->wrong_file_zerofile);
        $is = new InspectionFfprobeService();
        $is->getDuration($this->wrong_file_subtitle);
    }

    public function testGetDuration()
    {
        $file1 = $this->resources_dir.'AUDIO.mp3';
        $file2 = $this->resources_dir.'CAMERA.mp4';
        $is = new InspectionFfprobeService(); //logger missing, it is not initialized here.
        $this->assertEquals(2, $is->getDuration($file1));
        $this->assertEquals(2, $is->getDuration($file2));
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testAutocompleteTrackWithoutPath()
    {
        $empty_track = new Track();
        $is = new InspectionFfprobeService();
        $is->autocompleteTrack($empty_track);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAutocompleteTrackFileWithoutMultimediaContent()
    {
        $wrong_track = new Track();
        $is = new InspectionFfprobeService();
        $wrong_track->setPath($this->wrong_file_textfile);
        $is->autocompleteTrack($wrong_track);
    }

    public function testAutocompleteTrackOnlyAudio()
    {
        $file = $this->resources_dir.'AUDIO.mp3';
        $track = new Track();
        $is = new InspectionFfprobeService();
        $track->setPath($file);
        $is->autocompleteTrack($track);

        $mime_type = 'audio/mpeg';
        $bitrate = '129072';
        $duration = 2;
        $size = '16437';
        $acodec = 'mp3';

        // Test no video properties are set
        $this->assertEmpty($track->getVcodec());
        $this->assertEmpty($track->getFramerate());
        $this->assertEmpty($track->getWidth());
        $this->assertEmpty($track->getHeight());

        // Test general and audio properties
        $this->assertEquals($mime_type, $track->getMimetype());
        $this->assertEquals($acodec, $track->getAcodec());
        $this->assertEquals($bitrate, $track->getBitrate());
        $this->assertEquals($duration, $track->getDuration());
        $this->assertEquals($size, $track->getSize());
        $this->assertTrue($track->getOnlyAudio());
    }

    public function testAutocompleteTrackWithAudioAndVideo()
    {
        $file1 = $this->resources_dir.'CAMERA.mp4';
        $file2 = $this->resources_dir.'SCREEN.mp4';
        $track1 = new Track();
        $track2 = new Track();
        $is = new InspectionFfprobeService();
        $track1->setPath($file1);
        $track2->setPath($file2);
        $is->autocompleteTrack($track1);
        $is->autocompleteTrack($track2);

        $mime_type1 = 'video/mp4';
        $bitrate1 = 4300000;
        $duration1 = '2';
        $size1 = '551784';

        $vcodec1 = 'h264';
        $framerate1 = '25/1'; // Also works with $framerate = '25';
        $width1 = '960';
        $height1 = '720';

        $acodec1 = 'aac';

        // Test general properties
        $this->assertEquals($mime_type1, $track1->getMimetype());
        $this->assertTrue($track1->getBitrate() > $bitrate1);
        $this->assertEquals($duration1, $track1->getDuration());
        $this->assertEquals($size1, $track1->getSize());

        // Test video properties
        $this->assertEquals($vcodec1, $track1->getVcodec());
        $this->assertEquals($framerate1, $track1->getFramerate());
        $this->assertEquals($width1, $track1->getWidth());
        $this->assertEquals($height1, $track1->getHeight());

        // Test audio properties
        $this->assertFalse($track1->getOnlyAudio());
        $this->assertEquals($acodec1, $track1->getAcodec());

        $mime_type2 = 'video/mp4';
        $bitrate2 = 847600;
        $duration2 = 2;
        $size2 = 116545;

        $vcodec2 = 'h264';
        $framerate2 = '100/11';
        $width2 = 1200;
        $height2 = 900;

        $acodec2 = 'aac';

        // Test general properties
        $this->assertEquals($mime_type2, $track2->getMimetype());
        $this->assertEquals($bitrate2, $track2->getBitrate());
        $this->assertEquals($duration2, $track2->getDuration());
        $this->assertEquals($size2, $track2->getSize());

        // Test video properties
        $this->assertEquals($vcodec2, $track2->getVcodec());
        $this->assertEquals($framerate2, $track2->getFramerate());
        $this->assertEquals($width2, $track2->getWidth());
        $this->assertEquals($height2, $track2->getHeight());

        // Test audio properties
        $this->assertFalse($track2->getOnlyAudio());
        $this->assertEquals($acodec2, $track2->getAcodec());
    }
}
