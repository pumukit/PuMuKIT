<?php

declare(strict_types=1);

namespace Pumukit\WorkflowBundle\Tests\EventListener;

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Monolog\Logger;
use Pumukit\CoreBundle\Services\i18nService;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\JobCreator;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\MediaType\Document;
use Pumukit\SchemaBundle\Document\MediaType\Image;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\Exif;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Document\ValueObject\Url;
use Pumukit\SchemaBundle\EventListener\MultimediaObjectListener;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\TextIndexService;
use Pumukit\WebTVBundle\PumukitWebTVBundle;
use Pumukit\WorkflowBundle\EventListener\JobGeneratorListener;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @IgnoreAnnotation("dataProvider")
 *
 * @internal
 *
 * @coversNothing
 */
class JobGeneratorListenerTest extends PumukitTestCase
{
    private $logger;
    private $listener;
    private $trackDispatcher;
    private $jobGeneratorListener;
    private $i18nService;
    private $projectDir;
    private $factoryService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        parent::setUp();

        $this->logger = new Logger('test');
        $this->i18nService = new i18nService(['en', 'es'], 'en');
        $this->projectDir = static::$kernel->getContainer()->getParameter('kernel.project_dir');
        $profileService = static::$kernel->getContainer()->get(ProfileService::class);

        $jobCreator = static::$kernel->getContainer()->get(JobCreator::class);
        $this->jobGeneratorListener = new JobGeneratorListener($this->dm, $jobCreator, $profileService, $this->logger);
        $dispatcher = new EventDispatcher();
        $this->listener = new MultimediaObjectListener($this->dm, new TextIndexService(), $this->logger);
        $dispatcher->addListener('multimediaobject.update', [$this->listener, 'postUpdate']);
        $this->trackDispatcher = static::$kernel->getContainer()->get('pumukitschema.track_dispatcher');
        $this->factoryService = static::$kernel->getContainer()->get(FactoryService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->logger = null;
        $this->jobGeneratorListener = null;
        $this->listener = null;
        $this->trackDispatcher = null;
        gc_collect_cycles();
    }

    public function testGenerateJobsForVideo()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);
        $multimediaObject->setType(MultimediaObject::TYPE_VIDEO);
        $track = $this->generateVideoMedia();
        $multimediaObject->addTrack($track);
        $multimediaObject = $this->generateTagForMultimediaObject($multimediaObject);
        $this->dm->flush();

        $this->invokeMethod($this->jobGeneratorListener, 'checkMultimediaObject', [$multimediaObject]);

        $jobs = $this->dm->getRepository(Job::class)->findBy(['mm_id' => $multimediaObject->getId()]);

        $this->assertCount(1, $jobs);
    }

    public function testGenerateJobsForAudio()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);
        $multimediaObject->setType(MultimediaObject::TYPE_AUDIO);
        $track = $this->generateAudioMedia();
        $multimediaObject->addTrack($track);
        $multimediaObject = $this->generateTagForMultimediaObject($multimediaObject);
        $this->dm->flush();

        $this->invokeMethod($this->jobGeneratorListener, 'checkMultimediaObject', [$multimediaObject]);

        $jobs = $this->dm->getRepository(Job::class)->findBy(['mm_id' => $multimediaObject->getId()]);

        $this->assertCount(1, $jobs);
    }

    public function testGenerateJobsForImage()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);
        $multimediaObject->setType(MultimediaObject::TYPE_IMAGE);
        $track = $this->generateImageMedia();
        $multimediaObject->addImage($track);
        $multimediaObject = $this->generateTagForMultimediaObject($multimediaObject);
        $this->dm->flush();

        $this->invokeMethod($this->jobGeneratorListener, 'checkMultimediaObject', [$multimediaObject]);

        $jobs = $this->dm->getRepository(Job::class)->findBy(['mm_id' => $multimediaObject->getId()]);

        $this->assertCount(1, $jobs);
    }

    public function testGenerateJobsForDocument()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);
        $multimediaObject->setType(MultimediaObject::TYPE_DOCUMENT);
        $track = $this->generateDocumentMedia();
        $multimediaObject->addDocument($track);
        $multimediaObject = $this->generateTagForMultimediaObject($multimediaObject);
        $this->dm->flush();

        $this->invokeMethod($this->jobGeneratorListener, 'checkMultimediaObject', [$multimediaObject]);

        $jobs = $this->dm->getRepository(Job::class)->findBy(['mm_id' => $multimediaObject->getId()]);

        $this->assertCount(1, $jobs);
    }

    private function generateTagForMultimediaObject(MultimediaObject $multimediaObject): MultimediaObject
    {
        $pubChannel = new Tag();
        $pubChannel->setCod('PUBCHANNELS');
        $pubChannel->setTitle('PUBCHANNELS');
        $this->dm->persist($pubChannel);

        $tag = new Tag();
        $tag->setCod(PumukitWebTVBundle::WEB_TV_TAG);
        $tag->setTitle('PUCHWEBTV');
        $tag->setParent($pubChannel);

        $this->dm->persist($tag);

        $multimediaObject->addTag($tag);

        $this->dm->flush();

        return $multimediaObject;
    }

    private function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    private function generateVideoMedia(): MediaInterface
    {
        $originalName = 'originalName'.rand();
        $description = i18nText::create($this->i18nService->generateI18nText('18nDescription'));
        $language = 'en';
        $tags = Tags::create(['master', 'profile:master_copy', 'display']);
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

    private function generateAudioMedia(): MediaInterface
    {
        $originalName = 'originalName'.rand();
        $description = i18nText::create($this->i18nService->generateI18nText('18nDescription'));
        $language = 'en';
        $tags = Tags::create(['master', 'profile:master_copy', 'display']);
        $views = 0;
        $url = Url::create('');

        $path = Path::create($this->projectDir.'/tests/files/pumukit.mp3');
        $storage = Storage::create($url, $path);
        $mediaMetadata = VideoAudio::create('{"streams":[{"index":0,"codec_name":"mp3","codec_long_name":"MP3 (MPEG audio layer 3)","codec_type":"audio","codec_tag_string":"[0][0][0][0]","codec_tag":"0x0000","sample_fmt":"fltp","sample_rate":"44100","channels":2,"channel_layout":"stereo","bits_per_sample":0,"initial_padding":0,"r_frame_rate":"0\\/0","avg_frame_rate":"0\\/0","time_base":"1\\/14112000","start_pts":0,"start_time":"0.000000","duration_ts":14376960,"duration":"1.018776","bit_rate":"128000","disposition":{"default":0,"dub":0,"original":0,"comment":0,"lyrics":0,"karaoke":0,"forced":0,"hearing_impaired":0,"visual_impaired":0,"clean_effects":0,"attached_pic":0,"timed_thumbnails":0,"captions":0,"descriptions":0,"metadata":0,"dependent":0,"still_image":0}}],"format":{"filename":"\\/srv\\/pumukit\\/public\\/storage\\/masters\\/662608a27328d054160eaf83\\/662609cb9c32cd029b05e814.mp3","nb_streams":1,"nb_programs":0,"format_name":"mp3","format_long_name":"MP2\\/3 (MPEG audio layer 2\\/3)","start_time":"0.000000","duration":"1.018776","size":"16437","bit_rate":"129072","probe_score":51,"tags":{"encoder":"Lavf53.21.0"}}}');

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

    private function generateImageMedia(): MediaInterface
    {
        $originalName = 'originalName'.rand();
        $description = i18nText::create($this->i18nService->generateI18nText('18nDescription'));
        $language = 'en';
        $tags = Tags::create(['master', 'profile:master_copy', 'display']);
        $views = 0;
        $url = Url::create('');

        $path = Path::create($this->projectDir.'/tests/files/pumukit.png');
        $storage = Storage::create($url, $path);
        $mediaMetadata = Exif::create("[{\n  \"SourceFile\": \"/srv/pumukit/public/storage/downloads/661f85b5e6ecc78ad4061045/661f87cc92ee2dabd10f5e02.png\",\n  \"ExifToolVersion\": 12.57,\n  \"FileName\": \"661f87cc92ee2dabd10f5e02.png\",\n  \"Directory\": \"/srv/pumukit/public/storage/downloads/661f85b5e6ecc78ad4061045\",\n  \"FileSize\": \"8.4 kB\",\n  \"FileModifyDate\": \"2024:04:17 08:26:52+00:00\",\n  \"FileAccessDate\": \"2024:04:17 08:26:52+00:00\",\n  \"FileInodeChangeDate\": \"2024:04:17 08:26:52+00:00\",\n  \"FilePermissions\": \"-rw-rw-r--\",\n  \"FileType\": \"PNG\",\n  \"FileTypeExtension\": \"png\",\n  \"MIMEType\": \"image/png\",\n  \"ImageWidth\": 341,\n  \"ImageHeight\": 75,\n  \"BitDepth\": 8,\n  \"ColorType\": \"RGB with Alpha\",\n  \"Compression\": \"Deflate/Inflate\",\n  \"Filter\": \"Adaptive\",\n  \"Interlace\": \"Noninterlaced\",\n  \"SignificantBits\": \"8 8 8 8\",\n  \"Software\": \"gnome-screenshot\",\n  \"CreationTime\": \"2024:03:01 09:19:12\",\n  \"ImageSize\": \"341x75\",\n  \"Megapixels\": 0.026\n}]\n");

        return Image::create(
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

    private function generateDocumentMedia(): MediaInterface
    {
        $originalName = 'originalName'.rand();
        $description = i18nText::create($this->i18nService->generateI18nText('18nDescription'));
        $language = 'en';
        $tags = Tags::create(['master', 'profile:master_copy', 'display']);
        $views = 0;
        $url = Url::create('');

        $path = Path::create($this->projectDir.'/tests/files/pumukit.pdf');
        $storage = Storage::create($url, $path);
        $mediaMetadata = Exif::create("[{\n  \"SourceFile\": \"/srv/pumukit/public/storage/downloads/661f85b5e6ecc78ad4061045/661f87943a20f14a5009c752.pdf\",\n  \"ExifToolVersion\": 12.57,\n  \"FileName\": \"661f87943a20f14a5009c752.pdf\",\n  \"Directory\": \"/srv/pumukit/public/storage/downloads/661f85b5e6ecc78ad4061045\",\n  \"FileSize\": \"3.7 MB\",\n  \"FileModifyDate\": \"2024:04:17 08:25:56+00:00\",\n  \"FileAccessDate\": \"2024:04:17 08:25:56+00:00\",\n  \"FileInodeChangeDate\": \"2024:04:17 08:25:56+00:00\",\n  \"FilePermissions\": \"-rw-rw-r--\",\n  \"FileType\": \"PDF\",\n  \"FileTypeExtension\": \"pdf\",\n  \"MIMEType\": \"application/pdf\",\n  \"PDFVersion\": 1.4,\n  \"Linearized\": \"No\",\n  \"Author\": \"Bundestag Alemán Sección de Relaciones públicas\",\n  \"CreateDate\": \"2021:06:24 12:21:58+02:00\",\n  \"ModifyDate\": \"2021:07:05 16:28:42+02:00\",\n  \"HasXFA\": \"No\",\n  \"Language\": \"es-ES\",\n  \"TaggedPDF\": \"Yes\",\n  \"XMPToolkit\": \"Adobe XMP Core 5.6-c017 91.164464, 2020/06/15-10:20:05        \",\n  \"InstanceID\": \"uuid:94b3c8cb-f85a-864f-9498-a9ef4626a754\",\n  \"OriginalDocumentID\": \"adobe:docid:indd:f34df984-b951-11e2-8c33-dda980bf7d48\",\n  \"DocumentID\": \"xmp.id:8c7f8aea-b760-43e6-9289-7e615142712d\",\n  \"RenditionClass\": \"proof:pdf\",\n  \"DerivedFromInstanceID\": \"xmp.iid:7cf8859b-8dca-49c9-b357-583d6b45feba\",\n  \"DerivedFromDocumentID\": \"xmp.did:b3a3e1f9-f3d1-445a-9782-1b0b446a7142\",\n  \"DerivedFromOriginalDocumentID\": \"adobe:docid:indd:f34df984-b951-11e2-8c33-dda980bf7d48\",\n  \"DerivedFromRenditionClass\": \"default\",\n  \"HistoryAction\": \"converted\",\n  \"HistoryParameters\": \"from application/x-indesign to application/pdf\",\n  \"HistorySoftwareAgent\": \"Adobe InDesign 16.2 (Macintosh)\",\n  \"HistoryChanged\": \"/\",\n  \"HistoryWhen\": \"2021:06:24 12:21:58+02:00\",\n  \"MetadataDate\": \"2021:07:05 16:27:49+02:00\",\n  \"CreatorTool\": \"Adobe InDesign 16.2 (Macintosh)\",\n  \"Format\": \"application/pdf\",\n  \"Title\": \"Ley Fundamental de la República Federal de Alemania\",\n  \"Creator\": \"Bundestag Alemán Sección de Relaciones públicas\",\n  \"Producer\": \"Adobe PDF Library 15.0\",\n  \"Trapped\": false,\n  \"Part\": 1,\n  \"SlugChecksum\": 21510995,\n  \"SlugFamily\": \"Melior Com\",\n  \"SlugFontKind\": \"OpenType - TT\",\n  \"SlugFontSense_12_Checksum\": 21510995,\n  \"SlugFoundry\": \"Linotype AG\",\n  \"SlugOutlineFileSize\": 0,\n  \"SlugPostScriptName\": \"MeliorCom\",\n  \"SlugVersion\": 1.01,\n  \"SchemasNamespaceURI\": \"http://ns.adobe.com/pdf/1.3/\",\n  \"SchemasPrefix\": \"pdf\",\n  \"SchemasSchema\": \"Adobe PDF\",\n  \"SchemasPropertyCategory\": \"internal\",\n  \"SchemasPropertyDescription\": \"A name object indicating whether the document has been modified to include trapping information\",\n  \"SchemasPropertyName\": \"Trapped\",\n  \"SchemasPropertyValueType\": \"Text\",\n  \"SchemasValueTypeDescription\": \"Identifies a portion of a document. This can be a position at which the document has been changed since the most recent event history (stEvt:changed). For a resource within an xmpMM:Ingredients list, the ResourceRef uses this type to identify both the portion of the containing document that refers to the resource, and the portion of the referenced resource that is referenced.\",\n  \"SchemasValueTypeNamespaceURI\": \"http://ns.adobe.com/xap/1.0/sType/Part#\",\n  \"SchemasValueTypePrefix\": \"stPart\",\n  \"SchemasValueTypeType\": \"Part\",\n  \"PageLayout\": \"SinglePage\",\n  \"PageMode\": \"UseOutlines\",\n  \"PageCount\": 146\n}]\n");

        return Document::create(
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
