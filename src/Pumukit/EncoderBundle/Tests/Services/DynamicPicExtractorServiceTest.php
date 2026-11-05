<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use Psr\Log\NullLogger;
use Pumukit\CoreBundle\Services\i18nService;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Services\DynamicPicExtractorService;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Services\MultimediaObjectPicService;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
#[CoversNothing]
class DynamicPicExtractorServiceTest extends PumukitTestCase
{
    private string $dir;

    public function setUp(): void
    {
        static::bootKernel(['environment' => 'test']);
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/'.uniqid('dynamicpic', true);
        mkdir($this->dir);
    }

    public function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
        parent::tearDown();
        gc_collect_cycles();
    }

    public function testTheInputIsOneArgument(): void
    {
        $file = $this->dir.'/a b;c.mp4';
        copy(self::$kernel->getContainer()->getParameter('kernel.project_dir').'/tests/files/pumukit.mp4', $file);

        $container = self::$kernel->getContainer();
        $picService = new MultimediaObjectPicService($this->dm, $container->get('pumukitschema.pic_dispatcher'), $this->dir, '/uploads', false);
        $extractor = new DynamicPicExtractorService($this->dm, $picService, new NullLogger(), $this->dir, 'cp {{input}} {{output}}');

        $multimediaObject = $container->get('pumukitschema.factory')->createMultimediaObject($container->get('pumukitschema.factory')->createSeries());
        $track = $this->track($file);
        $multimediaObject->addTrack($track);

        $this->assertTrue($extractor->extract($multimediaObject, $track));
        $this->assertFileEquals($file, $multimediaObject->getPics()[0]->getPath());
    }

    private function track(string $file): Track
    {
        $i18n = new i18nService(['en'], 'en');

        return Track::create(
            'original',
            i18nText::create($i18n->generateI18nText('description')),
            'en',
            Tags::create(['display']),
            false,
            true,
            0,
            Storage::create(StorageUrl::create(''), Path::create($file)),
            VideoAudio::create('{"streams":[{"codec_type":"video","codec_name":"h264","width":1920,"height":1080,"r_frame_rate":"30\/1","avg_frame_rate":"30\/1","nb_frames":"300","duration":"10.000000"}],"format":{"duration":"10.000000","size":"116169","bit_rate":"92935"}}')
        );
    }
}
