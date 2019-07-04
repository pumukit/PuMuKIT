<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Event\TrackEvent;
use Pumukit\SchemaBundle\Services\TrackEventDispatcherService;

class TrackEventDispatcherServiceTest extends WebTestCase
{
    const EMPTY_TITLE = 'EMTPY TITLE';
    const EMPTY_URL = 'EMTPY URL';

    private $trackDispatcher;
    private $dispatcher;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dispatcher = static::$kernel->getContainer()
          ->get('event_dispatcher');

        MockUpTrackListener::$called = false;
        MockUpTrackListener::$title = self::EMPTY_TITLE;
        MockUpTrackListener::$url = self::EMPTY_URL;

        $this->trackDispatcher = new TrackEventDispatcherService($this->dispatcher);
    }

    public function tearDown()
    {
        $this->dispatcher = null;
        $this->trackDispatcher = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::TRACK_CREATE, function ($event, $name) {
            $this->assertTrue($event instanceof TrackEvent);
            $this->assertEquals(SchemaEvents::TRACK_CREATE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $track = $event->getTrack();

            MockUpTrackListener::$called = true;
            MockUpTrackListener::$title = $multimediaObject->getTitle();
            MockUpTrackListener::$url = $track->getUrl();
        });

        $this->assertFalse(MockUpTrackListener::$called);
        $this->assertEquals(self::EMPTY_TITLE, MockUpTrackListener::$title);
        $this->assertEquals(self::EMPTY_URL, MockUpTrackListener::$url);

        $title = 'test title';
        $url = 'http://testtrack.com';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $track = new Track();
        $track->setUrl($url);

        $this->trackDispatcher->dispatchCreate($multimediaObject, $track);

        $this->assertTrue(MockUpTrackListener::$called);
        $this->assertEquals($title, MockUpTrackListener::$title);
        $this->assertEquals($url, MockUpTrackListener::$url);
    }

    public function testDispatchUpdate()
    {
        $this->dispatcher->addListener(SchemaEvents::TRACK_UPDATE, function ($event, $name) {
            $this->assertTrue($event instanceof TrackEvent);
            $this->assertEquals(SchemaEvents::TRACK_UPDATE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $track = $event->getTrack();

            MockUpTrackListener::$called = true;
            MockUpTrackListener::$title = $multimediaObject->getTitle();
            MockUpTrackListener::$url = $track->getUrl();
        });

        $this->assertFalse(MockUpTrackListener::$called);
        $this->assertEquals(self::EMPTY_TITLE, MockUpTrackListener::$title);
        $this->assertEquals(self::EMPTY_URL, MockUpTrackListener::$url);

        $title = 'test title';
        $url = 'http://testtrack.com';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $track = new Track();
        $track->setUrl($url);

        $updateUrl = 'http://testtrackupdate.com';
        $track->setUrl($updateUrl);

        $this->trackDispatcher->dispatchUpdate($multimediaObject, $track);

        $this->assertTrue(MockUpTrackListener::$called);
        $this->assertEquals($title, MockUpTrackListener::$title);
        $this->assertEquals($updateUrl, MockUpTrackListener::$url);
    }

    public function testDispatchDelete()
    {
        $this->dispatcher->addListener(SchemaEvents::TRACK_DELETE, function ($event, $name) {
            $this->assertTrue($event instanceof TrackEvent);
            $this->assertEquals(SchemaEvents::TRACK_DELETE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $track = $event->getTrack();

            MockUpTrackListener::$called = true;
            MockUpTrackListener::$title = $multimediaObject->getTitle();
            MockUpTrackListener::$url = $track->getUrl();
        });

        $this->assertFalse(MockUpTrackListener::$called);
        $this->assertEquals(self::EMPTY_TITLE, MockUpTrackListener::$title);
        $this->assertEquals(self::EMPTY_URL, MockUpTrackListener::$url);

        $title = 'test title';
        $url = 'http://testtrack.com';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $track = new Track();
        $track->setUrl($url);

        $this->trackDispatcher->dispatchDelete($multimediaObject, $track);

        $this->assertTrue(MockUpTrackListener::$called);
        $this->assertEquals($title, MockUpTrackListener::$title);
        $this->assertEquals($url, MockUpTrackListener::$url);
    }
}

class MockUpTrackListener
{
    public static $called = false;
    public static $title = TrackEventDispatcherServiceTest::EMPTY_TITLE;
    public static $url = TrackEventDispatcherServiceTest::EMPTY_URL;
}
