<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\Mongo\MongoCommandCounter;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\WebTVBundle\PumukitWebTVBundle;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversNothing]
final class SeriesCardCountsTest extends PumukitTestCase
{
    private FactoryService $factory;
    private Tag $webTv;

    public function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
        parent::setUp();
        $this->factory = self::getContainer()->get(FactoryService::class);

        $this->webTv = new Tag();
        $this->webTv->setCod(PumukitWebTVBundle::WEB_TV_TAG);
        $this->dm->persist($this->webTv);
        $this->dm->flush();
    }

    public function testTheSeriesCardsShowTheSameCountsAsBefore(): void
    {
        $series = [$this->series('Alpha', 2, 1), $this->series('Bravo', 1, 2), $this->series('Charlie', 0, 0)];

        self::assertSame(['Alpha' => '3', 'Bravo' => '3', 'Charlie' => '0'], $this->countsOnTheCards($series));
    }

    public function testTheSeriesCardsDoNotCountEachSeries(): void
    {
        $series = array_map(fn (string $title): Series => $this->series($title, 1, 1), ['Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo']);

        $counter = MongoCommandCounter::start();
        $this->countsOnTheCards($series);
        $counter->stop();

        self::assertSame(0, $counter->count('count', 'MultimediaObject'));
    }

    private function countsOnTheCards(array $series): array
    {
        $container = self::getContainer();
        $container->get('request_stack')->push(Request::create('/'));
        $html = $container->get('twig')->render('@PumukitWebTV/Resources/template_list.html.twig', ['objects' => $series, 'objectByCol' => 3]);

        $counts = [];
        (new Crawler($html))->filter('.image_representation')->each(static function (Crawler $card) use (&$counts): void {
            $counts[trim((string) $card->filter('a')->attr('title'))] = trim($card->filter('.series_count_mmobj')->text());
        });
        ksort($counts);

        return $counts;
    }

    private function series(string $title, int $published, int $blocked): Series
    {
        $series = $this->factory->createSeries();
        $series->setTitle($title);
        foreach ([MultimediaObject::STATUS_PUBLISHED => $published, MultimediaObject::STATUS_BLOCKED => $blocked] as $status => $number) {
            for ($i = 0; $i < $number; ++$i) {
                $multimediaObject = $this->factory->createMultimediaObject($series);
                $multimediaObject->setStatus($status);
                $multimediaObject->addTag($this->dm->getRepository(Tag::class)->find($this->webTv->getId()));
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
        }
        $this->dm->flush();

        return $series;
    }
}
