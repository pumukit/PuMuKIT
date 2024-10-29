<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Services\i18nService;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Event\TrackEvent;
use Pumukit\SchemaBundle\Services\TrackEventDispatcherService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class TrackEventDispatcherServiceTest extends WebTestCase
{
    public const EMPTY_TITLE = 'EMTPY TITLE';
    public const EMPTY_URL = 'EMTPY URL';

    private $trackDispatcher;
    private $dispatcher;
    private $i18nService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dispatcher = static::$kernel->getContainer()->get('event_dispatcher');
        $this->i18nService = new i18nService(['en', 'es'], 'en');

        MockUpTrackListener::$called = false;
        MockUpTrackListener::$title = self::EMPTY_TITLE;
        MockUpTrackListener::$url = self::EMPTY_URL;

        $this->trackDispatcher = new TrackEventDispatcherService($this->dispatcher);
    }

    public function tearDown(): void
    {
        $this->dispatcher = null;
        $this->trackDispatcher = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::TRACK_CREATE, function ($event, $name) {
            static::assertInstanceOf(TrackEvent::class, $event);
            static::assertEquals(SchemaEvents::TRACK_CREATE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $track = $event->getMedia();

            MockUpTrackListener::$called = true;
            MockUpTrackListener::$title = $multimediaObject->getTitle();
            MockUpTrackListener::$url = $track->storage()->url()->url();
        });

        static::assertFalse(MockUpTrackListener::$called);
        static::assertEquals(self::EMPTY_TITLE, MockUpTrackListener::$title);
        static::assertEquals(self::EMPTY_URL, MockUpTrackListener::$url);

        $title = 'test title';
        $url = 'http://testtrack.com';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $track = $this->generateTrackMedia();

        $this->trackDispatcher->dispatchCreate($multimediaObject, $track);

        static::assertTrue(MockUpTrackListener::$called);
        static::assertEquals($title, MockUpTrackListener::$title);
        static::assertEquals($url, MockUpTrackListener::$url);
    }

    public function testDispatchUpdate()
    {
        $this->dispatcher->addListener(SchemaEvents::TRACK_UPDATE, function ($event, $name) {
            static::assertInstanceOf(TrackEvent::class, $event);
            static::assertEquals(SchemaEvents::TRACK_UPDATE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $track = $event->getMedia();

            MockUpTrackListener::$called = true;
            MockUpTrackListener::$title = $multimediaObject->getTitle();
            MockUpTrackListener::$url = $track->storage()->url()->url();
        });

        static::assertFalse(MockUpTrackListener::$called);
        static::assertEquals(self::EMPTY_TITLE, MockUpTrackListener::$title);
        static::assertEquals(self::EMPTY_URL, MockUpTrackListener::$url);

        $title = 'test title';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $track = $this->generateTrackMedia();

        $this->trackDispatcher->dispatchUpdate($multimediaObject, $track);

        static::assertTrue(MockUpTrackListener::$called);
        static::assertEquals($title, MockUpTrackListener::$title);
    }

    public function testDispatchDelete()
    {
        $this->dispatcher->addListener(SchemaEvents::TRACK_DELETE, function ($event, $name) {
            static::assertInstanceOf(TrackEvent::class, $event);
            static::assertEquals(SchemaEvents::TRACK_DELETE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $track = $event->getMedia();

            MockUpTrackListener::$called = true;
            MockUpTrackListener::$title = $multimediaObject->getTitle();
            MockUpTrackListener::$url = $track->storage()->url()->url();
        });

        static::assertFalse(MockUpTrackListener::$called);
        static::assertEquals(self::EMPTY_TITLE, MockUpTrackListener::$title);
        static::assertEquals(self::EMPTY_URL, MockUpTrackListener::$url);

        $title = 'test title';
        $url = 'http://testtrack.com';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $track = $this->generateTrackMedia();

        $this->trackDispatcher->dispatchDelete($multimediaObject, $track);

        static::assertTrue(MockUpTrackListener::$called);
        static::assertEquals($title, MockUpTrackListener::$title);
        static::assertEquals($url, MockUpTrackListener::$url);
    }

    private function generateTrackMedia(): MediaInterface
    {
        $originalName = 'originalName'.random_int(0, mt_getrandmax());
        $description = i18nText::create($this->i18nService->generateI18nText('18nDescription'));
        $language = 'en';
        $tags = Tags::create(['display']);
        $views = 0;
        $url = StorageUrl::create('http://testtrack.com');
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

class MockUpTrackListener
{
    public static $called = false;
    public static $title = TrackEventDispatcherServiceTest::EMPTY_TITLE;
    public static $url = TrackEventDispatcherServiceTest::EMPTY_URL;
}
