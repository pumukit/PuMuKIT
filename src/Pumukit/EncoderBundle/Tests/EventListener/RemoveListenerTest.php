<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\EventListener;

use Pumukit\CoreBundle\Services\i18nService;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\TrackService;

/**
 * @internal
 *
 * @coversNothing
 */
class RemoveListenerTest extends PumukitTestCase
{
    private $repoJobs;
    private $repoMmobj;
    private $repoSeries;
    private $trackService;
    private $factoryService;
    private $i18nService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        parent::setUp();

        $this->repoJobs = $this->dm->getRepository(Job::class);
        $this->repoMmobj = $this->dm->getRepository(MultimediaObject::class);
        $this->repoSeries = $this->dm->getRepository(Series::class);

        $this->factoryService = static::$kernel->getContainer()->get(FactoryService::class);
        $this->trackService = static::$kernel->getContainer()->get(TrackService::class);
        $this->i18nService = new i18nService(['en', 'es'], 'en');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repoJobs = null;
        $this->repoMmobj = null;
        $this->repoSeries = null;
        $this->factoryService = null;
        $this->trackService = null;
        gc_collect_cycles();
    }

    public function testPostTrackRemove()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $track = $this->generateTrackMedia();
        $multimediaObject->addTrack($track);
        $this->dm->flush();

        $this->trackService->removeTrackFromMultimediaObject($multimediaObject, $track->id());

        static::assertCount(1, $this->repoSeries->findAll());
        static::assertCount(2, $this->repoMmobj->findAll());
        static::assertCount(0, $this->repoJobs->findAll());
    }

    private function generateTrackMedia(): MediaInterface
    {
        $originalName = 'originalName'.random_int(0, mt_getrandmax());
        $description = i18nText::create($this->i18nService->generateI18nText('18nDescription'));
        $language = 'en';
        $tags = Tags::create(['display', 'opencast']);
        $views = 0;
        $url = StorageUrl::create('');
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
