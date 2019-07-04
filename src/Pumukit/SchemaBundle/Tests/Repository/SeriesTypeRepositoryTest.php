<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Pumukit\SchemaBundle\Document\SeriesType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class SeriesTypeRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $factoryService;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository('PumukitSchemaBundle:SeriesType')
        ;
        $this->factoryService = static::$kernel->getContainer()
            ->get('pumukitschema.factory')
        ;

        $this->dm->getDocumentCollection('PumukitSchemaBundle:SeriesType')
            ->remove([])
        ;
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        $this->factoryService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testRepositoryEmpty()
    {
        $this->assertEquals(0, count($this->repo->findAll()));
    }

    public function testRepository()
    {
        $seriesType = new SeriesType();

        $name = 'Series Type 1';
        $description = 'Series Type description';
        $cod = 'Cod_1';

        $seriesType->setName($name);
        $seriesType->setDescription($description);
        $seriesType->setCod($cod);

        $this->dm->persist($seriesType);
        $this->dm->flush();

        $this->assertEquals(1, count($this->repo->findAll()));
        $this->assertEquals($seriesType, $this->repo->find($seriesType->getId()));
    }

    public function testContainsSeries()
    {
        $this->markTestSkipped('S');

        $seriesType = new SeriesType();
        $this->dm->persist($seriesType);
        $this->dm->flush();

        $series = $this->factoryService->createSeries();
        $series->setSeriesType($seriesType);
        $this->dm->persist($series);
        $this->dm->persist($seriesType);
        $this->dm->flush();

        $this->assertTrue($seriesType->containsSeries($series));
    }
}
