<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Event\TrackEvent;
use Pumukit\SchemaBundle\Services\TrackEventDispatcherService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class TrackEventDispatcherServiceTest extends WebTestCase
{
    public const EMPTY_TITLE = 'EMTPY TITLE';
    public const EMPTY_URL = 'EMTPY URL';

    private $trackDispatcher;
    private $dispatcher;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dispatcher = static::$kernel->getContainer()->get('event_dispatcher');

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
            $track = $event->getTrack();

            MockUpTrackListener::$called = true;
            MockUpTrackListener::$title = $multimediaObject->getTitle();
            MockUpTrackListener::$url = $track->getUrl();
        });

        static::assertFalse(MockUpTrackListener::$called);
        static::assertEquals(self::EMPTY_TITLE, MockUpTrackListener::$title);
        static::assertEquals(self::EMPTY_URL, MockUpTrackListener::$url);

        $title = 'test title';
        $url = 'http://testtrack.com';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $track = new Track();
        $track->setUrl($url);

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
            $track = $event->getTrack();

            MockUpTrackListener::$called = true;
            MockUpTrackListener::$title = $multimediaObject->getTitle();
            MockUpTrackListener::$url = $track->getUrl();
        });

        static::assertFalse(MockUpTrackListener::$called);
        static::assertEquals(self::EMPTY_TITLE, MockUpTrackListener::$title);
        static::assertEquals(self::EMPTY_URL, MockUpTrackListener::$url);

        $title = 'test title';
        $url = 'http://testtrack.com';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $track = new Track();
        $track->setUrl($url);

        $updateUrl = 'http://testtrackupdate.com';
        $track->setUrl($updateUrl);

        $this->trackDispatcher->dispatchUpdate($multimediaObject, $track);

        static::assertTrue(MockUpTrackListener::$called);
        static::assertEquals($title, MockUpTrackListener::$title);
        static::assertEquals($updateUrl, MockUpTrackListener::$url);
    }

    public function testDispatchDelete()
    {
        $this->dispatcher->addListener(SchemaEvents::TRACK_DELETE, function ($event, $name) {
            static::assertInstanceOf(TrackEvent::class, $event);
            static::assertEquals(SchemaEvents::TRACK_DELETE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $track = $event->getTrack();

            MockUpTrackListener::$called = true;
            MockUpTrackListener::$title = $multimediaObject->getTitle();
            MockUpTrackListener::$url = $track->getUrl();
        });

        static::assertFalse(MockUpTrackListener::$called);
        static::assertEquals(self::EMPTY_TITLE, MockUpTrackListener::$title);
        static::assertEquals(self::EMPTY_URL, MockUpTrackListener::$url);

        $title = 'test title';
        $url = 'http://testtrack.com';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $track = new Track();
        $track->setUrl($url);

        $this->trackDispatcher->dispatchDelete($multimediaObject, $track);

        static::assertTrue(MockUpTrackListener::$called);
        static::assertEquals($title, MockUpTrackListener::$title);
        static::assertEquals($url, MockUpTrackListener::$url);
    }
}

class MockUpTrackListener
{
    public static $called = false;
    public static $title = TrackEventDispatcherServiceTest::EMPTY_TITLE;
    public static $url = TrackEventDispatcherServiceTest::EMPTY_URL;
}
