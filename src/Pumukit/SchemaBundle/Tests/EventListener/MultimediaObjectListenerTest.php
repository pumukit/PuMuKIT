<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\EventListener;

use Monolog\Logger;
use Pumukit\CoreBundle\Services\i18nService;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Document\ValueObject\Url;
use Pumukit\SchemaBundle\EventListener\MultimediaObjectListener;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\TextIndexService;
use Pumukit\SchemaBundle\Services\TrackEventDispatcherService;
use Pumukit\SchemaBundle\Services\TrackService;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 *
 * @coversNothing
 */
class MultimediaObjectListenerTest extends PumukitTestCase
{
    private $listener;
    private $trackDispatcher;
    private $trackService;
    private $factoryService;
    private $i18nService;
    private $projectDir;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();

        $dispatcher = new EventDispatcher();
        $logger = new Logger('test');
        $this->listener = new MultimediaObjectListener($this->dm, new TextIndexService(), $logger);
        $dispatcher->addListener('multimediaobject.update', [$this->listener, 'postUpdate']);
        $this->trackDispatcher = static::$kernel->getContainer()->get(TrackEventDispatcherService::class);
        $this->factoryService = static::$kernel->getContainer()->get(FactoryService::class);
        $logger = new Logger('test');
        $this->trackService = new TrackService($this->dm, $this->trackDispatcher, $logger);
        $this->i18nService = new i18nService(['en', 'es'], 'en');
        $this->projectDir = static::$kernel->getContainer()->getParameter('kernel.project_dir');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->listener = null;
        $this->trackDispatcher = null;
        $this->trackService = null;
        gc_collect_cycles();
    }

    public function testPostUpdate()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $t1 = $this->generateTrackMedia([]);
        $t2 = $this->generateTrackMedia([]);
        $t3 = $this->generateTrackMedia([]);
        $t4 = $this->generateTrackMedia([]);
        $t5 = $this->generateTrackMedia(['master']);

        $this->trackService->addTrackToMultimediaObject($multimediaObject, $t1, false);
        $this->trackService->addTrackToMultimediaObject($multimediaObject, $t2, false);
        $this->trackService->addTrackToMultimediaObject($multimediaObject, $t3, false);
        $this->trackService->addTrackToMultimediaObject($multimediaObject, $t4, false);
        $this->trackService->addTrackToMultimediaObject($multimediaObject, $t5, true);

        $this->trackService->updateTrackInMultimediaObject($multimediaObject, $t5);

        $multimediaObject2 = $this->factoryService->createMultimediaObject($series);

        $track1 = $this->generateTrackMedia(['master']);
        $track2 = $this->generateTrackMedia([]);

        static::assertEquals(null, $multimediaObject2->getMaster());
        $this->trackService->addTrackToMultimediaObject($multimediaObject2, $track1, true);
        static::assertEquals($track1, $multimediaObject2->getMaster());
        $this->trackService->addTrackToMultimediaObject($multimediaObject2, $track2, true);
        static::assertEquals($track1, $multimediaObject2->getMaster());
    }

    private function generateTrackMedia(array $tags): MediaInterface
    {
        $originalName = 'originalName'.rand();
        $description = i18nText::create($this->i18nService->generateI18nText('18nDescription'));
        $language = 'en';
        $tags = Tags::create($tags);
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
}
