<?php
/**
 * This test signs a bug in 'doctrine/mongodb-odm'. The bug is similar to #981.
 * Pumukit2 has the next workaround while the bug is not fixed:.
 *
 * +      $mm->setSeries($series);
 * -      $series->addMultimediaObject($mm);
 */

namespace Pumukit\SchemaBundle\Tests\Other;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class MultimediaObjectInSeriesTest extends WebTestCase
{
    private $dm;
    private $seriesRepo;
    private $mmobjRepo;
    private $factoryService;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $container = static::$kernel->getContainer();
        $this->factoryService = $container->get('pumukitschema.factory');
        $this->dm = $container->get('doctrine_mongodb')->getManager();
        $this->seriesRepo = $this->dm->getRepository(Series::class);
        $this->mmobjRepo = $this->dm->getRepository(MultimediaObject::class);

        $this->dm->getDocumentCollection(Series::class)->remove([]);
        $this->dm->getDocumentCollection(MultimediaObject::class)->remove([]);
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->factoryService = null;
        $this->dm = null;
        $this->seriesRepo = null;
        $this->mmobjRepo = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testCreateNewMultimediaObject()
    {
        $series = $this->factoryService->createSeries();

        $this->factoryService->createMultimediaObject($series);

        $coll_mms = $this->seriesRepo->getMultimediaObjects($series);

        //echo "Assert\n";
        $this->assertEquals(1, count($coll_mms));

        //echo "Foreach\n";
        $i = 0;
        foreach ($coll_mms as $mm) {
            ++$i;
            //echo "\t - ", $mm->getId(), "\n";
        }
        $this->assertEquals(1, $i);
    }

    public function testRelationSimple()
    {
        $series1 = $this->factoryService->createSeries();
        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $mm13 = $this->factoryService->createMultimediaObject($series1);

        $id = $series1->getId();
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
