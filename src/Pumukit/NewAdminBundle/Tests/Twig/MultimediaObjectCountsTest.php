<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Twig;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\Mongo\MongoCommandCounter;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\NewAdminBundle\Twig\PumukitAdminExtension;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\FactoryService;

/**
 * @internal
 */
#[CoversNothing]
final class MultimediaObjectCountsTest extends PumukitTestCase
{
    private FactoryService $factory;
    private PumukitAdminExtension $extension;

    public function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
        parent::setUp();
        $this->factory = self::getContainer()->get(FactoryService::class);
        $this->extension = self::getContainer()->get(PumukitAdminExtension::class);
        $this->extension->reset();
    }

    public function testThePrefetchedCountsAreTheCountsOfEachSeries(): void
    {
        $series = [$this->seriesWithVideos(3), $this->seriesWithVideos(0), $this->seriesWithVideos(1)];
        $video = $this->factory->createMultimediaObject($series[1]);
        $expected = array_map(fn (Series $oneSeries): int => $this->extension->countMultimediaObjects($oneSeries), $series);

        $this->extension->prefetchMultimediaObjectCounts([$video, ...$series]);

        self::assertSame([3, 1, 1], $expected);
        self::assertSame($expected, array_map(fn (Series $oneSeries): int => $this->extension->countMultimediaObjects($oneSeries), $series));
    }

    public function testThePrefetchCountsAPageOfSeriesInOneQuery(): void
    {
        $series = array_map(fn (int $videos): Series => $this->seriesWithVideos($videos), [2, 1, 0, 4, 3]);

        $counter = MongoCommandCounter::start();
        $this->extension->prefetchMultimediaObjectCounts($series);
        foreach ($series as $oneSeries) {
            $this->extension->countMultimediaObjects($oneSeries);
        }
        $counter->stop();

        self::assertSame(1, $counter->count());
    }

    public function testAnIterableThatCannotBeRewoundIsNotConsumed(): void
    {
        $series = $this->seriesWithVideos(2);
        $objects = (static function () use ($series) {
            yield $series;
        })();

        $this->extension->prefetchMultimediaObjectCounts($objects);

        self::assertSame([$series], iterator_to_array($objects));
        self::assertSame(2, $this->extension->countMultimediaObjects($series));
    }

    private function seriesWithVideos(int $videos): Series
    {
        $series = $this->factory->createSeries();
        for ($i = 0; $i < $videos; ++$i) {
            $this->factory->createMultimediaObject($series)->setStatus(MultimediaObject::STATUS_PUBLISHED);
        }
        $live = $this->factory->createMultimediaObject($series);
        $live->setType(MultimediaObject::TYPE_LIVE);
        $this->dm->flush();

        return $series;
    }
}
