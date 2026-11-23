<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\Mongo\MongoCommandCounter;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\WebTVBundle\PumukitWebTVBundle;
use Pumukit\WebTVBundle\Services\SearchService;

/**
 * @internal
 */
#[CoversNothing]
final class SearchServiceYearsTest extends PumukitTestCase
{
    private FactoryService $factory;
    private SearchService $searchService;
    private Tag $webTv;

    public function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
        parent::setUp();
        $this->factory = self::getContainer()->get(FactoryService::class);
        $this->searchService = self::getContainer()->get(SearchService::class);

        $this->webTv = new Tag();
        $this->webTv->setCod(PumukitWebTVBundle::WEB_TV_TAG);
        $this->dm->persist($this->webTv);
        $this->dm->flush();
    }

    public function testTheSeriesYearsKeepTheOrderOfTheSeriesAndSkipTheRepeatedOnes(): void
    {
        $this->seriesWithAPublishedVideo('2019-05-01');
        $this->seriesWithAPublishedVideo('2021-01-10');
        $this->seriesWithAPublishedVideo('2019-11-30');
        $this->seriesWithAPublishedVideo('2017-03-03');
        $this->factory->createSeries();

        self::assertSame([0 => '2019', 1 => '2021', 3 => '2017'], $this->searchService->getYears(SearchService::SERIES));
    }

    public function testTheSeriesYearsDoNotQueryEachSeries(): void
    {
        foreach (['2018-01-01', '2019-01-01', '2020-01-01', '2021-01-01', '2022-01-01'] as $date) {
            $this->seriesWithAPublishedVideo($date);
        }
        $this->dm->clear();

        $counter = MongoCommandCounter::start();
        $this->searchService->getYears(SearchService::SERIES);
        $counter->stop();

        self::assertSame(1, $counter->count('find', 'Series'));
    }

    private function seriesWithAPublishedVideo(string $publicDate): void
    {
        $series = $this->factory->createSeries();
        $series->setPublicDate(new \DateTime($publicDate));
        $multimediaObject = $this->factory->createMultimediaObject($series);
        $multimediaObject->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $multimediaObject->addTag($this->dm->getRepository(Tag::class)->find($this->webTv->getId()));
        $this->dm->flush();
    }
}
