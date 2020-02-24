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

    public function setUp(): void
    {
        if (false === TestCommand::commandExists('ffprobe')) {
            static::markTestSkipped('FFprobe test marks skipped (No ffprobe command).');
        }

        $this->resources_dir = realpath(__DIR__.'/../Resources').DIRECTORY_SEPARATOR;
        $this->wrong_file_textfile = $this->resources_dir.'textfile.txt';
        $this->wrong_file_zerofile = $this->resources_dir.'zerosizefile.txt';
        $this->wrong_file_subtitle = $this->resources_dir.'subtitle.vtt';
        $this->vid_no_audio = $this->resources_dir.'SCREEN.mp4';
    }

    public function tearDown(): void
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
    public function testGetDurationFileNotExists(): void
    {
        $is = new InspectionFfprobeService();
        $is->getDuration('http://trololo.com');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetDurationFileWithoutMultimediaContent(): void
    {
        $is = new InspectionFfprobeService();
        $is->getDuration($this->wrong_file_textfile);
        $is = new InspectionFfprobeService();
        $is->getDuration($this->wrong_file_zerofile);
        $is = new InspectionFfprobeService();
        $is->getDuration($this->wrong_file_subtitle);
    }

    public function testGetDuration(): void
    {
        $file1 = $this->resources_dir.'AUDIO.mp3';
        $file2 = $this->resources_dir.'CAMERA.mp4';
        $is = new InspectionFfprobeService(); //logger missing, it is not initialized here.
        static::assertEquals(2, $is->getDuration($file1));
        static::assertEquals(2, $is->getDuration($file2));
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testAutocompleteTrackWithoutPath(): void
    {
        $empty_track = new Track();
        $is = new InspectionFfprobeService();
        $is->autocompleteTrack($empty_track);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAutocompleteTrackFileWithoutMultimediaContent(): void
    {
        $wrong_track = new Track();
        $is = new InspectionFfprobeService();
        $wrong_track->setPath($this->wrong_file_textfile);
        $is->autocompleteTrack($wrong_track);
    }

    public function testAutocompleteTrackOnlyAudio(): void
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
        static::assertEmpty($track->getVcodec());
        static::assertEmpty($track->getFramerate());
        static::assertEmpty($track->getWidth());
        static::assertEmpty($track->getHeight());

        // Test general and audio properties
        static::assertEquals($mime_type, $track->getMimetype());
        static::assertEquals($acodec, $track->getAcodec());
        static::assertEquals($bitrate, $track->getBitrate());
        static::assertEquals($duration, $track->getDuration());
        static::assertEquals($size, $track->getSize());
        static::assertTrue($track->getOnlyAudio());
    }

    public function testAutocompleteTrackWithAudioAndVideo(): void
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
        static::assertEquals($mime_type1, $track1->getMimetype());
        static::assertTrue($track1->getBitrate() > $bitrate1);
        static::assertEquals($duration1, $track1->getDuration());
        static::assertEquals($size1, $track1->getSize());

        // Test video properties
        static::assertEquals($vcodec1, $track1->getVcodec());
        static::assertEquals($framerate1, $track1->getFramerate());
        static::assertEquals($width1, $track1->getWidth());
        static::assertEquals($height1, $track1->getHeight());

        // Test audio properties
        static::assertFalse($track1->getOnlyAudio());
        static::assertEquals($acodec1, $track1->getAcodec());

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
        static::assertEquals($mime_type2, $track2->getMimetype());
        static::assertEquals($bitrate2, $track2->getBitrate());
        static::assertEquals($duration2, $track2->getDuration());
        static::assertEquals($size2, $track2->getSize());

        // Test video properties
        static::assertEquals($vcodec2, $track2->getVcodec());
        static::assertEquals($framerate2, $track2->getFramerate());
        static::assertEquals($width2, $track2->getWidth());
        static::assertEquals($height2, $track2->getHeight());

        // Test audio properties
        static::assertFalse($track2->getOnlyAudio());
        static::assertEquals($acodec2, $track2->getAcodec());
    }
}
