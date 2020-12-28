<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Event\SeriesEvent;
use Pumukit\SchemaBundle\Services\SeriesEventDispatcherService;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 * @coversNothing
 */
class SeriesEventDispatcherServiceTest extends PumukitTestCase
{
    const EMPTY_TITLE = 'EMPTY_TITLE';

    private $seriesDispatcher;
    private $dispatcher;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();

        $this->dispatcher = new EventDispatcher();

        MockUpSeriesListener::$called = false;
        MockUpSeriesListener::$title = self::EMPTY_TITLE;

        $this->seriesDispatcher = new SeriesEventDispatcherService($this->dispatcher);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->dispatcher = null;
        $this->seriesDispatcher = null;
        gc_collect_cycles();
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::SERIES_CREATE, function ($event, $title) {
            static::assertInstanceOf(SeriesEvent::class, $event);
            static::assertEquals(SchemaEvents::SERIES_CREATE, $title);

            $series = $event->getSeries();

            MockUpSeriesListener::$called = true;
            MockUpSeriesListener::$title = $series->getTitle();
        });

        static::assertFalse(MockUpSeriesListener::$called);
        static::assertEquals(self::EMPTY_TITLE, MockUpSeriesListener::$title);

        $title = 'test_title';

        $series = new Series();
        $series->setTitle($title);

        $this->dm->persist($series);
        $this->dm->flush();

        $this->seriesDispatcher->dispatchCreate($series);

        static::assertTrue(MockUpSeriesListener::$called);
        static::assertEquals($title, MockUpSeriesListener::$title);
    }

    public function testDispatchUpdate()
    {
        $this->dispatcher->addListener(SchemaEvents::SERIES_UPDATE, function ($event, $title) {
            static::assertInstanceOf(SeriesEvent::class, $event);
            static::assertEquals(SchemaEvents::SERIES_UPDATE, $title);

            $series = $event->getSeries();

            MockUpSeriesListener::$called = true;
            MockUpSeriesListener::$title = $series->getTitle();
        });

        static::assertFalse(MockUpSeriesListener::$called);
        static::assertEquals(self::EMPTY_TITLE, MockUpSeriesListener::$title);

        $title = 'test_title';

        $series = new Series();
        $series->setTitle($title);

        $this->dm->persist($series);
        $this->dm->flush();

        $updateTitle = 'New_title';
        $series->setTitle($updateTitle);

        $this->dm->persist($series);
        $this->dm->flush();

        $this->seriesDispatcher->dispatchUpdate($series);

        static::assertTrue(MockUpSeriesListener::$called);
        static::assertEquals($updateTitle, MockUpSeriesListener::$title);
    }

    public function testDispatchDelete()
    {
        $this->dispatcher->addListener(SchemaEvents::SERIES_DELETE, function ($event, $title) {
            static::assertInstanceOf(SeriesEvent::class, $event);
            static::assertEquals(SchemaEvents::SERIES_DELETE, $title);

            $series = $event->getSeries();

            MockUpSeriesListener::$called = true;
            MockUpSeriesListener::$title = $series->getTitle();
        });

        static::assertFalse(MockUpSeriesListener::$called);
        static::assertEquals(self::EMPTY_TITLE, MockUpSeriesListener::$title);

        $title = 'test_title';

        $series = new Series();
        $series->setTitle($title);

        $this->dm->persist($series);
        $this->dm->flush();

        $this->seriesDispatcher->dispatchDelete($series);

        static::assertTrue(MockUpSeriesListener::$called);
        static::assertEquals($title, MockUpSeriesListener::$title);
    }
}

class MockUpSeriesListener
{
    public static $called = false;
    public static $title = SeriesEventDispatcherServiceTest::EMPTY_TITLE;
}
