<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\Tests\EventListener;

use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\Attributes\CoversNothing;
use Psr\Log\NullLogger;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\CoreBundle\Services\i18nService;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\StatsBundle\Document\ViewsDailyStats;
use Pumukit\StatsBundle\Document\ViewsDailyTotals;
use Pumukit\StatsBundle\Document\ViewsLog;
use Pumukit\StatsBundle\EventListener\Log;
use Pumukit\StatsBundle\Services\ViewsDailyStatsService;
use Pumukit\StatsBundle\Services\VisitorAnonymizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[CoversNothing]
class LogTest extends PumukitTestCase
{
    private $repo;
    private $factoryService;
    private $tokenStorage;
    private $i18nService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(ViewsLog::class);
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');
        $this->tokenStorage = static::getContainer()->get('security.token_storage');
        $this->i18nService = new i18nService(['en', 'es'], 'en');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->repo = null;
        $this->factoryService = null;
        $this->tokenStorage = null;
        gc_collect_cycles();
    }

    public function testonMultimediaObjectViewed()
    {
        $requestStack = $this->createMockRequestStack();
        $service = new Log($this->dm, $requestStack, $this->tokenStorage, new VisitorAnonymizer('secret'), static::getContainer()->get(ViewsDailyStatsService::class), new NullLogger());

        $event = $this->createEvent();
        $service->onMultimediaObjectViewed($event);
        static::assertCount(1, $this->repo->findAll());

        $log = $this->repo->findAll()[0];
        static::assertSame('203.0.113.0', $log->getIp());
        static::assertMatchesRegularExpression('/^[0-9a-f]{20}$/', $log->getVisitor());
    }

    public function testTheDailyStatsCountEveryViewAndEachVisitorOnce(): void
    {
        $event = $this->createEvent();
        foreach (['http://localhost/video/1', 'http://localhost/iframe/1'] as $referer) {
            $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.42', 'HTTP_REFERER' => $referer, 'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile/15E148']);
            $requestStack = $this->createStub(RequestStack::class);
            $requestStack->method('getMainRequest')->willReturn($request);
            (new Log($this->dm, $requestStack, $this->tokenStorage, new VisitorAnonymizer('secret'), static::getContainer()->get(ViewsDailyStatsService::class), new NullLogger()))->onMultimediaObjectViewed($event);
        }

        $stats = $this->dm->getDocumentCollection(ViewsDailyStats::class)->findOne(['multimediaObject' => new ObjectId($event->getMultimediaObject()->getId())]);
        static::assertSame(2, $stats['views']);
        static::assertSame(1, $stats['uniqueViews']);
        static::assertSame(1, $stats['sources']['portal']);
        static::assertSame(1, $stats['sources']['embed']);
        static::assertSame(2, $stats['devices']['mobile']);
        static::assertSame(2, array_sum((array) $stats['hours']));

        $totals = $this->dm->getDocumentCollection(ViewsDailyTotals::class)->findOne([]);
        static::assertSame(2, $totals['views']);
        static::assertSame(1, $totals['uniqueViews']);
    }

    public function testonMultimediaObjectWithoutTrackViewed()
    {
        $requestStack = $this->createMockRequestStack();
        $service = new Log($this->dm, $requestStack, $this->tokenStorage, new VisitorAnonymizer('secret'), static::getContainer()->get(ViewsDailyStatsService::class), new NullLogger());

        $event = $this->createEvent(false);
        $service->onMultimediaObjectViewed($event);
        static::assertCount(1, $this->repo->findAll());
    }

    private function createMockRequestStack()
    {
        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.42']);
        $requestStack = $this->getMockBuilder(RequestStack::class)->getMock();
        $requestStack->expects(static::once())->method('getMainRequest')->willReturn($request);

        return $requestStack;
    }

    private function createEvent($withTrack = true)
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        if ($withTrack) {
            $track = $this->generateTrackMedia();
            $multimediaObject->addTrack($track);
            $this->dm->persist($multimediaObject);
            $this->dm->flush();
        } else {
            $track = null;
        }

        return new ViewedEvent($multimediaObject, $track);
    }

    private function generateTrackMedia(): MediaInterface
    {
        $originalName = 'originalName'.random_int(0, mt_getrandmax());
        $description = i18nText::create($this->i18nService->generateI18nText('18nDescription'));
        $language = 'en';
        $tags = Tags::create(['display']);
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
