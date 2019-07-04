<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Event\SeriesEvent;
use Pumukit\SchemaBundle\Services\SeriesEventDispatcherService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 * @coversNothing
 */
class SeriesEventDispatcherServiceTest extends WebTestCase
{
    const EMPTY_TITLE = 'EMPTY_TITLE';

    private $dm;
    private $seriesDispatcher;
    private $dispatcher;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb.odm.document_manager')
        ;
        $this->dispatcher = new EventDispatcher();

        $this->dm->getDocumentCollection(Series::class)->remove([]);
        $this->dm->flush();

        MockUpSeriesListener::$called = false;
        MockUpSeriesListener::$title = self::EMPTY_TITLE;

        $this->seriesDispatcher = new SeriesEventDispatcherService($this->dispatcher);
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->dispatcher = null;
        $this->seriesDispatcher = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::SERIES_CREATE, function ($event, $title) {
            $this->assertTrue($event instanceof SeriesEvent);
            $this->assertEquals(SchemaEvents::SERIES_CREATE, $title);

            $series = $event->getSeries();

            MockUpSeriesListener::$called = true;
            MockUpSeriesListener::$title = $series->getTitle();
        });

        $this->assertFalse(MockUpSeriesListener::$called);
        $this->assertEquals(self::EMPTY_TITLE, MockUpSeriesListener::$title);

        $title = 'test_title';

        $series = new Series();
        $series->setTitle($title);

        $this->dm->persist($series);
        $this->dm->flush();

        $this->seriesDispatcher->dispatchCreate($series);

        $this->assertTrue(MockUpSeriesListener::$called);
        $this->assertEquals($title, MockUpSeriesListener::$title);
    }

    public function testDispatchUpdate()
    {
        $this->dispatcher->addListener(SchemaEvents::SERIES_UPDATE, function ($event, $title) {
            $this->assertTrue($event instanceof SeriesEvent);
            $this->assertEquals(SchemaEvents::SERIES_UPDATE, $title);

            $series = $event->getSeries();

            MockUpSeriesListener::$called = true;
            MockUpSeriesListener::$title = $series->getTitle();
        });

        $this->assertFalse(MockUpSeriesListener::$called);
        $this->assertEquals(self::EMPTY_TITLE, MockUpSeriesListener::$title);

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

        $this->assertTrue(MockUpSeriesListener::$called);
        $this->assertEquals($updateTitle, MockUpSeriesListener::$title);
    }

    public function testDispatchDelete()
    {
        $this->dispatcher->addListener(SchemaEvents::SERIES_DELETE, function ($event, $title) {
            $this->assertTrue($event instanceof SeriesEvent);
            $this->assertEquals(SchemaEvents::SERIES_DELETE, $title);

            $series = $event->getSeries();

            MockUpSeriesListener::$called = true;
            MockUpSeriesListener::$title = $series->getTitle();
        });

        $this->assertFalse(MockUpSeriesListener::$called);
        $this->assertEquals(self::EMPTY_TITLE, MockUpSeriesListener::$title);

        $title = 'test_title';

        $series = new Series();
        $series->setTitle($title);

        $this->dm->persist($series);
        $this->dm->flush();

        $this->seriesDispatcher->dispatchDelete($series);

        $this->assertTrue(MockUpSeriesListener::$called);
        $this->assertEquals($title, MockUpSeriesListener::$title);
    }
}

class MockUpSeriesListener
{
    public static $called = false;
    public static $title = SeriesEventDispatcherServiceTest::EMPTY_TITLE;
}
