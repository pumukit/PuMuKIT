<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

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
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\MediaUpdater;

/**
 * @internal
 *
 * @coversNothing
 */
class MediaUpdaterTest extends PumukitTestCase
{
    private $i18nService;
    private $factoryService;

    private $mediaUpdater;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();

        $this->factoryService = static::$kernel->getContainer()->get(FactoryService::class);
        $this->i18nService = new i18nService(['en', 'es'], 'en');
        $this->mediaUpdater = new MediaUpdater($this->dm);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();
        gc_collect_cycles();
    }

    public function testUpdateTags()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);
        $track = $this->generateTrackMedia();

        $this->dm->persist($track);
        $multimediaObject->addTrack($track);
        $this->dm->flush();

        $tags = Tags::create(['display', 'tag']);
        $this->mediaUpdater->updateTags($multimediaObject, $track, $tags);

        $this->assertEquals($tags, $track->tags());
    }

    public function testUpdateDescription()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);
        $track = $this->generateTrackMedia();

        $this->dm->persist($track);
        $multimediaObject->addTrack($track);
        $this->dm->flush();

        $this->mediaUpdater->updateDescription($multimediaObject, $track, i18nText::create($this->i18nService->generateI18nText('new 18nDescription')));
        $this->assertEquals('new 18nDescription', $track->description()->textFromLocale('en'));
    }

    public function testUpdateLanguage()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);
        $track = $this->generateTrackMedia();

        $this->dm->persist($track);
        $multimediaObject->addTrack($track);
        $this->dm->flush();

        $this->mediaUpdater->updateLanguage($multimediaObject, $track, 'gl');
        $this->assertEquals('gl', $track->language());
    }

    public function testUpdateHide()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);
        $track = $this->generateTrackMedia();

        $this->dm->persist($track);
        $multimediaObject->addTrack($track);
        $this->dm->flush();

        $this->mediaUpdater->updateHide($multimediaObject, $track, true);
        $this->assertTrue($track->isHide());
    }

    public function testUpdateDownload()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);
        $track = $this->generateTrackMedia();

        $this->dm->persist($track);
        $multimediaObject->addTrack($track);
        $this->dm->flush();

        $this->mediaUpdater->updateDownload($multimediaObject, $track, false);
        $this->assertFalse($track->isDownloadable());
    }

    private function generateTrackMedia(): MediaInterface
    {
        $originalName = 'originalName'.random_int(0, mt_getrandmax());
        $description = i18nText::create($this->i18nService->generateI18nText('18nDescription'));
        $language = 'en';
        $tags = Tags::create(['display']);
        $views = 0;
        $url = Url::create('');
        $path = Path::create('public/storage');
        $storage = Storage::create($url, $path);
        $mediaMetadata = VideoAudio::create('{"format":{"duration":"10.000000"}}');

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
