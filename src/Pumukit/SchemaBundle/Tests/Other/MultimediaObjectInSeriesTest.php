<?php
/**
 * This test signs a bug in 'doctrine/mongodb-odm'. The bug is similar to #981.
 * Pumukit has the next workaround while the bug is not fixed:.
 *
 * +      $mm->setSeries($series);
 * -      $series->addMultimediaObject($mm);
 */

namespace Pumukit\SchemaBundle\Tests\Other;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

/**
 * @internal
 * @coversNothing
 */
class MultimediaObjectInSeriesTest extends PumukitTestCase
{
    private $seriesRepo;
    private $mmobjRepo;
    private $factoryService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $container = static::$kernel->getContainer();
        $this->factoryService = $container->get('pumukitschema.factory');
        $this->seriesRepo = $this->dm->getRepository(Series::class);
        $this->mmobjRepo = $this->dm->getRepository(MultimediaObject::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();
        $this->factoryService = null;

        $this->seriesRepo = null;
        $this->mmobjRepo = null;
        gc_collect_cycles();
    }

    public function testCreateNewMultimediaObject()
    {
        $series = $this->factoryService->createSeries();
        $this->dm->persist($series);

        $this->factoryService->createMultimediaObject($series);

        $coll_mms = $this->seriesRepo->getMultimediaObjects($series);

        $this->assertCount(1, $coll_mms);

        $i = 0;
        foreach ($coll_mms as $mm) {
            ++$i;
        }
        $this->assertEquals(1, $i);
    }

    public function testRelationSimple(): void
    {
        $series1 = $this->factoryService->createSeries();
        $this->factoryService->createMultimediaObject($series1);
        $this->factoryService->createMultimediaObject($series1);
        $this->factoryService->createMultimediaObject($series1);

        $this->dm->clear();

        $i = 0;
        foreach ($this->seriesRepo->findAll() as $s) {
            foreach ($this->seriesRepo->getMultimediaObjects($s) as $mm) {
                ++$i;
            }
        }
        $this->assertEquals(3, $i);
    }
}
