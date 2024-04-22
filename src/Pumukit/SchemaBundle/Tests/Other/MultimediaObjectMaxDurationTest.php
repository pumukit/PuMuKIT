<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Other;

use Pumukit\CoreBundle\Services\i18nService;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Document\ValueObject\Url;
use Pumukit\SchemaBundle\Services\FactoryService;

/**
 * @internal
 *
 * @coversNothing
 */
class MultimediaObjectMaxDurationTest extends PumukitTestCase
{
    private $repo;
    private $factoryService;
    private $i18nService;
    private $projectDir;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(MultimediaObject::class);
        $this->factoryService = static::$kernel->getContainer()->get(FactoryService::class);
        $this->i18nService = new i18nService(['en', 'es'], 'en');
        $this->projectDir = static::$kernel->getContainer()->getParameter('kernel.project_dir');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        $this->factoryService = null;
        gc_collect_cycles();
    }

    public function testMaxDuration()
    {
        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $trackGenerated = $this->generateTrackMedia();
        $this->dm->persist($trackGenerated);
        $mm->addTrack($trackGenerated);

        $this->dm->flush();
        $this->dm->clear();

        $mm = $this->repo->find($mm->getId());
        $track = $mm->getTrackById($trackGenerated->id());

        static::assertEquals($track, $trackGenerated);
        static::assertEquals(116169, $track->metadata()->size());
        static::assertEquals(10, $track->metadata()->duration());
    }

    private function generateTrackMedia(): MediaInterface
    {
        $originalName = 'originalName'.random_int(0, mt_getrandmax());
        $description = i18nText::create($this->i18nService->generateI18nText('18nDescription'));
        $language = 'en';
        $tags = Tags::create(['display']);
        $views = 0;
        $url = Url::create('');

        $path = Path::create($this->projectDir.'/tests/files/pumukit.mp4');
        $storage = Storage::create($url, $path);
        $mediaMetadata = VideoAudio::create('{"streams":[{"index":0,"codec_name":"h264","codec_long_name":"H.264 \\/ AVC \\/ MPEG-4 AVC \\/ MPEG-4 part 10","profile":"High 4:4:4 Predictive","codec_type":"video","codec_tag_string":"avc1","codec_tag":"0x31637661","width":1920,"height":1080,"coded_width":1920,"coded_height":1080,"closed_captions":0,"film_grain":0,"has_b_frames":2,"pix_fmt":"yuv444p","level":40,"chroma_location":"left","field_order":"progressive","refs":1,"is_avc":"true","nal_length_size":"4","id":"0x1","r_frame_rate":"30\\/1","avg_frame_rate":"30\\/1","time_base":"1\\/15360","start_pts":0,"start_time":"0.000000","duration_ts":153600,"duration":"10.000000","bit_rate":"89370","bits_per_raw_sample":"8","nb_frames":"300","extradata_size":47,"disposition":{"default":1,"dub":0,"original":0,"comment":0,"lyrics":0,"karaoke":0,"forced":0,"hearing_impaired":0,"visual_impaired":0,"clean_effects":0,"attached_pic":0,"timed_thumbnails":0,"captions":0,"descriptions":0,"metadata":0,"dependent":0,"still_image":0},"tags":{"language":"und","handler_name":"VideoHandler","vendor_id":"[0][0][0][0]"}}],"format":{"filename":"\\/srv\\/pumukit\\/public\\/storage\\/masters\\/662608a27328d054160eaf83\\/6626097a07ef8d6b000d4f44.mp4","nb_streams":1,"nb_programs":0,"format_name":"mov,mp4,m4a,3gp,3g2,mj2","format_long_name":"QuickTime \\/ MOV","start_time":"0.000000","duration":"10.000000","size":"116169","bit_rate":"92935","probe_score":100,"tags":{"major_brand":"isom","minor_version":"512","compatible_brands":"isomiso2avc1mp41","encoder":"Lavf58.76.100"}}}');

        return Track::create(
            $originalName,
            $description,
            $language,
            $tags,
            false,
            true,
            $views,
            $storage,
            $mediaMetadata
        );
    }
}
