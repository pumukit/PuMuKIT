<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Tests\Twig;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\WebTVBundle\PumukitWebTVBundle;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversNothing]
final class SeriesTextCountTest extends PumukitTestCase
{
    public function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
        parent::setUp();
    }

    public function testTheSeriesTextCountsThePublishedVideosWhenNoCountIsGiven(): void
    {
        $series = $this->seriesWithPublishedVideos(2);

        $container = self::getContainer();
        $container->get('request_stack')->push(Request::create('/'));
        $html = $container->get('twig')->render('@PumukitWebTV/Misc/series_text.html.twig', ['object' => $series]);

        self::assertStringContainsString('[2 videos]', (new Crawler($html))->text());
    }

    private function seriesWithPublishedVideos(int $videos): Series
    {
        $factory = self::getContainer()->get(FactoryService::class);
        $webTv = new Tag();
        $webTv->setCod(PumukitWebTVBundle::WEB_TV_TAG);
        $this->dm->persist($webTv);

        $series = $factory->createSeries();
        for ($i = 0; $i < $videos; ++$i) {
            $multimediaObject = $factory->createMultimediaObject($series);
            $multimediaObject->setStatus(MultimediaObject::STATUS_PUBLISHED);
            $multimediaObject->addTag($webTv);
        }
        $this->dm->flush();

        return $series;
    }
}
