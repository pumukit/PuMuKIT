<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\Tests\EventListener;

use Pumukit\BasePlayerBundle\Event\ViewedEvent;
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
use Pumukit\StatsBundle\Document\ViewsLog;
use Pumukit\StatsBundle\EventListener\Log;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 *
 * @coversNothing
 */
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
        $this->tokenStorage = static::$kernel->getContainer()->get('security.token_storage');
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
        $service = new Log($this->dm, $requestStack, $this->tokenStorage);

        $event = $this->createEvent();
        $service->onMultimediaObjectViewed($event);
        static::assertCount(1, $this->repo->findAll());
    }

    public function testonMultimediaObjectWithoutTrackViewed()
    {
        $requestStack = $this->createMockRequestStack();
        $service = new Log($this->dm, $requestStack, $this->tokenStorage);

        $event = $this->createEvent(false);
        $service->onMultimediaObjectViewed($event);
        static::assertCount(1, $this->repo->findAll());
    }

    private function createMockRequestStack()
    {
        $request = Request::create('/');
        $requestStack = $this->getMockBuilder(RequestStack::class)->getMock();
        $requestStack->expects(static::once())->method('getMasterRequest')->willReturn($request);

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
