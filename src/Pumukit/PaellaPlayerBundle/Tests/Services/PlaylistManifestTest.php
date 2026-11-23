<?php

declare(strict_types=1);

namespace Pumukit\PaellaPlayerBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\Mongo\MongoCommandCounter;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\PaellaPlayerBundle\Services\PlaylistManifest;
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

/**
 * @internal
 */
#[CoversNothing]
final class PlaylistManifestTest extends PumukitTestCase
{
    private FactoryService $factory;

    public function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
        parent::setUp();
        $this->factory = self::getContainer()->get(FactoryService::class);
    }

    public function testAPlaylistListsItsPlayableVideosInOrderWithTheirRepetitions(): void
    {
        $series = $this->factory->createSeries();
        $first = $this->video($series, 'First', true);
        $hidden = $this->video($series, 'Not playable', false);
        $second = $this->video($series, 'Second', true);

        $playlist = $this->factory->createPlaylist();
        foreach ([$second, $first, $hidden, $second] as $multimediaObject) {
            $playlist->getPlaylist()->addMultimediaObject($multimediaObject);
        }
        $this->dm->flush();

        self::assertSame(['Second', 'First', 'Second'], $this->titles($playlist));
    }

    public function testASeriesListsItsVideosByRank(): void
    {
        $series = $this->factory->createSeries();
        $this->video($series, 'First', true);
        $this->video($series, 'Second', false);
        $this->video($series, 'Third', true);

        self::assertSame(['First', 'Second', 'Third'], $this->titles($series));
    }

    public function testThePlaylistDoesNotQueryEachVideo(): void
    {
        $series = $this->factory->createSeries();
        $playlist = $this->factory->createPlaylist();
        foreach (range(1, 5) as $number) {
            $playlist->getPlaylist()->addMultimediaObject($this->video($series, 'Video '.$number, true));
        }
        $this->dm->flush();
        $this->dm->clear();
        $playlist = $this->dm->getRepository(Series::class)->find($playlist->getId());

        $counter = MongoCommandCounter::start();
        $this->titles($playlist);
        $counter->stop();

        self::assertLessThanOrEqual(3, $counter->count('find', 'MultimediaObject'));
    }

    private function titles(Series $series): array
    {
        $method = new \ReflectionMethod(PlaylistManifest::class, 'generatePlaylistMetadata');
        $metadata = $method->invoke(self::getContainer()->get(PlaylistManifest::class), $series, 0, '/playlist');

        return array_map(static fn (array $video): string => $video['title'], $metadata['videos']);
    }

    private function video(Series $series, string $title, bool $playable): MultimediaObject
    {
        $multimediaObject = $this->factory->createMultimediaObject($series);
        $multimediaObject->setTitle($title);
        $multimediaObject->setStatus(MultimediaObject::STATUS_PUBLISHED);
        if ($playable) {
            $multimediaObject->addTrack(Track::create(
                'video.mp4',
                i18nText::create(['en' => '']),
                'en',
                Tags::create(['display']),
                false,
                false,
                0,
                Storage::create(StorageUrl::create(''), Path::create(self::getContainer()->getParameter('kernel.project_dir').'/tests/files/pumukit.mp4')),
                VideoAudio::create('{"format":{"duration":"10.000000"}}')
            ));
        }
        $this->dm->flush();

        return $multimediaObject;
    }
}
