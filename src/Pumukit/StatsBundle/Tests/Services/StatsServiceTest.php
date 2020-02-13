<?php

namespace Pumukit\StatsBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\StatsBundle\Document\ViewsLog;
use Pumukit\StatsBundle\Services\StatsService;

/**
 * @internal
 * @coversNothing
 */
class StatsServiceTest extends PumukitTestCase
{
    private $repo;
    private $factoryService;
    private $viewsService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(ViewsLog::class);
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');
        $this->viewsService = static::$kernel->getContainer()->get('pumukit_stats.stats');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        $this->factoryService = null;
        $this->viewsService = null;
        gc_collect_cycles();
    }

    public function testSimpleStatsService()
    {
        $list = $this->initContext();

        $service = new StatsService($this->dm);
        $mv = $service->getMostViewed([], 1, 1);
        static::assertCount(1, $mv);
        static::assertEquals($mv, [$list[3]]);

        $mv = $service->getMostViewed([], 30, 1);
        static::assertEquals($mv, [$list[5]]);

        $mv = $service->getMostViewed([], 1, 3);
        static::assertEquals($mv, [$list[3], $list[2], $list[1]]);

        $mv = $service->getMostViewed([], 30, 3);
        static::assertEquals($mv, [$list[5], $list[4], $list[3]]);

        $mv = $service->getMostViewed([], 30, 30);
        static::assertCount(5, $mv);
        static::assertEquals($mv, [$list[5], $list[4], $list[3], $list[2], $list[1]]);
    }

    public function testStatsServiceWithBlockedVideos()
    {
        $list = $this->initContext();
        $this->initTags($list);

        $service = new StatsService($this->dm);
        $mv = $service->getMostViewed(['tv'], 30, 3);
        static::assertEquals($mv, [$list[5], $list[4], $list[3]]);

        $mm = $list[5];
        foreach ($mm->getTags() as $tag) {
            $mm->removeTag($tag);
        }
        $this->dm->persist($mm);
        $this->dm->flush();

        $mv = $service->getMostViewed(['tv'], 30, 3);
        static::assertEquals($mv, [$list[4], $list[3], $list[2]]);
    }

    public function testStatsServiceWithTags()
    {
        $list = $this->initContext();
        $this->initTags($list);

        $service = new StatsService($this->dm);

        $mv = $service->getMostViewed(['1'], 30, 30);
        static::assertEquals($mv, [$list[1]]);

        $mv = $service->getMostViewed(['11'], 30, 30);
        static::assertEquals($mv, []);

        $mv = $service->getMostViewed(['1'], 1, 3);
        static::assertEquals($mv, [$list[1]]);
    }

    public function testStatsServiceUsingFilters()
    {
        $list = $this->initContext();
        $this->initTags($list);

        $filter = $this->dm->getFilterCollection()->enable('frontend');
        $filter->setParameter('pub_channel_tag', '1');

        $this->dm->getFilterCollection()->enable('personal');

        $service = new StatsService($this->dm);

        $mv = $service->getMostViewedUsingFilters(30, 30);
        static::assertEquals($mv, [$list[1]]);
    }

    public function testGetMmobjsMostViewedByRange()
    {
        $list = $this->initContext();
        $this->initTags($list);
        $service = new StatsService($this->dm);
        //Maps the list to give an output similar to function
        $listMapped = array_map(function ($a) {
            return [
                'mmobj' => $a,
                'num_viewed' => $a->getNumview(),
            ];
        }, $list);
        //Sorts by least viewed
        usort($listMapped, function ($a, $b) {
            return $a['num_viewed'] > $b['num_viewed'];
        });

        [$mostViewed, $total] = $service->getMmobjsMostViewedByRange([], ['sort' => 1]);

        static::assertEquals($listMapped, $mostViewed);

        //Sorts by most viewed
        usort($listMapped, function ($a, $b) {
            return $a['num_viewed'] < $b['num_viewed'];
        });

        [$mostViewed, $total] = $service->getMmobjsMostViewedByRange();
        static::assertEquals($listMapped, $mostViewed);
        static::assertCount($total, $listMapped);

        [$mostViewed, $total] = $service->getMmobjsMostViewedByRange(['title.en' => 'OTHER MMOBJ']);
        static::assertEquals([$listMapped[4]], $mostViewed);
        static::assertEquals($total, 1);

        [$mostViewed, $total] = $service->getMmobjsMostViewedByRange([], ['limit' => 0]);
        static::assertEquals([], $mostViewed);
        static::assertEquals($total, 5);
        [$mostViewed, $total] = $service->getMmobjsMostViewedByRange(['not_a_parameter' => 'not_a_value']);
        static::assertEquals($total, 0);
        [$mostViewed, $total] = $service->getMmobjsMostViewedByRange(['title.en' => 'New'], ['limit' => 2, 'from_date' => new \DateTime('-11 days')]);
        static::assertEquals([$listMapped[1], $listMapped[2]], $mostViewed);
        static::assertEquals(4, $total);
        [$mostViewed, $total] = $service->getMmobjsMostViewedByRange(['title.en' => 'New'], ['limit' => 2, 'from_date' => new \DateTime('-11 days'), 'page' => 1]);
        static::assertEquals([$listMapped[3], ['mmobj' => $list[3], 'num_viewed' => 0]], $mostViewed);
        static::assertEquals(4, $total);

        [$mostViewed, $total] = $service->getMmobjsMostViewedByRange([], ['from_date' => new \DateTime('-21 days'), 'to_date' => new \DateTime('-9 days')]);

        static::assertEquals([$listMapped[0], $listMapped[1]], array_slice($mostViewed, 0, 2));
        static::assertEquals(0, $mostViewed[2]['num_viewed']);
        static::assertEquals(0, $mostViewed[3]['num_viewed']);
        static::assertEquals(0, $mostViewed[4]['num_viewed']);
        static::assertEquals(5, $total);
    }

    public function testGetSeriesMostViewedByRange(): void
    {
        $service = new StatsService($this->dm);
        [$mostViewed, $total] = $service->getSeriesMostViewedByRange();
    }

    private function logView($when, MultimediaObject $multimediaObject, Track $track = null)
    {
        $log = new ViewsLog('/', '8.8.8.8', 'test', '', $multimediaObject->getId(), $multimediaObject->getSeries()->getId(), null);
        $log->setDate($when);
        $multimediaObject->incNumview();
        $this->dm->persist($log);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $log;
    }

    private function initContext()
    {
        $series = $this->factoryService->createSeries();
        $list = [];
        $list[1] = $this->factoryService->createMultimediaObject($series);
        $list[2] = $this->factoryService->createMultimediaObject($series);
        $list[3] = $this->factoryService->createMultimediaObject($series);
        $list[4] = $this->factoryService->createMultimediaObject($series);
        $list[5] = $this->factoryService->createMultimediaObject($series);

        foreach ($list as $i => $mm) {
            $mm->setStatus(MultimediaObject::STATUS_PUBLISHED);
            $this->dm->persist($mm);
        }
        $this->dm->flush();

        $this->logView(new \DateTime('now'), $list[1]);
        $this->logView(new \DateTime('now'), $list[3]);
        $this->logView(new \DateTime('now'), $list[3]);
        $this->logView(new \DateTime('now'), $list[3]);
        $this->logView(new \DateTime('now'), $list[2]);
        $this->logView(new \DateTime('now'), $list[2]);

        $this->logView(new \DateTime('-10 days'), $list[4]);
        $this->logView(new \DateTime('-10 days'), $list[4]);
        $this->logView(new \DateTime('-10 days'), $list[4]);
        $this->logView(new \DateTime('-10 days'), $list[4]);

        $this->logView(new \DateTime('-20 days'), $list[5]);
        $this->logView(new \DateTime('-20 days'), $list[5]);
        $this->logView(new \DateTime('-20 days'), $list[5]);
        $this->logView(new \DateTime('-20 days'), $list[5]);
        $this->logView(new \DateTime('-20 days'), $list[5]);

        $this->viewsService->aggregateViewsLog();

        $list[1]->setTitle('OTHER MMOBJ');

        return $list;
    }

    private function initTags($list)
    {
        $globalTag = new Tag();
        $globalTag->setCod('tv');
        $this->dm->persist($globalTag);

        $tags = [];
        foreach ($list as $i => $mm) {
            $tag = new Tag();
            $tag->setCod($i);
            $this->dm->persist($tag);
            $tags[$i] = $tag;
        }
        $this->dm->flush();

        foreach ($list as $i => $mm) {
            $mm->addTag($globalTag);
            $mm->addTag($tags[$i]);
            $this->dm->persist($mm);
        }
        $this->dm->flush();
    }
}
