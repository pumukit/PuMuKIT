<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
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
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
final class SearchControllerTest extends PumukitTestCase
{
    private KernelBrowser $client;
    private Tag $webTv;
    private Tag $category;
    private Series $series;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = self::createClient();
        $this->dm = self::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $root = $this->tag('ROOT');
        $unesco = $this->tag('UNESCO', $root);
        $this->category = $this->tag('U310000', $unesco);
        $this->webTv = $this->tag(PumukitWebTVBundle::WEB_TV_TAG, $root);
        $this->series = self::getContainer()->get(FactoryService::class)->createSeries(null, ['en' => 'Physics']);
    }

    public function testTheVideosCanBeFilteredByCategory(): void
    {
        $this->video('Video of the category', true);
        $this->video('Video of another category');

        $crawler = $this->client->request('GET', '/searchmultimediaobjects', ['tags' => [$this->category->getCod()]]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(['Video of the category'], $this->titles($crawler->filter('a.multimediaobject')));
    }

    public function testTheVideosCanBeSearchedWithWildcards(): void
    {
        $this->video('Physics video');
        $this->video('Another video');

        $crawler = $this->client->request('GET', '/searchmultimediaobjects', ['search' => 'Phys*']);

        $this->assertResponseIsSuccessful();
        $this->assertSame(['Physics video'], $this->titles($crawler->filter('a.multimediaobject')));
    }

    public function testTheSeriesCanBeSearchedWithWildcards(): void
    {
        $this->video('Physics video');

        $this->client->request('GET', '/searchseries', ['search' => 'Phys*']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.total-search', '1');
    }

    private function titles($links): array
    {
        $titles = array_values(array_unique($links->each(static fn ($link): string => trim((string) $link->attr('title')))));
        sort($titles);

        return $titles;
    }

    private function tag(string $cod, ?Tag $parent = null): Tag
    {
        $tag = new Tag();
        $tag->setCod($cod);
        $tag->setTitle($cod);
        $tag->setDisplay(true);
        if ($parent) {
            $tag->setParent($parent);
        }
        $this->dm->persist($tag);
        $this->dm->flush();

        return $tag;
    }

    private function video(string $title, bool $category = false): void
    {
        $multimediaObject = self::getContainer()->get(FactoryService::class)->createMultimediaObject($this->series);
        $multimediaObject->setTitle($title);
        $multimediaObject->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $multimediaObject->addTag($this->webTv);
        if ($category) {
            $multimediaObject->addTag($this->category);
        }
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
        $this->dm->flush();
    }
}
