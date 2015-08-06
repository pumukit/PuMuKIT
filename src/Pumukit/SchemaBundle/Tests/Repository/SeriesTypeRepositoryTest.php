<?php
namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\SeriesType;

class SeriesTypeRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $factoryService;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();
        $this->dm = $kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository('PumukitSchemaBundle:SeriesType');
        $this->factoryService = $kernel->getContainer()
            ->get('pumukitschema.factory');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:SeriesType')
            ->remove(array());
        $this->dm->flush();
    }

    public function testRepositoryEmpty()
    {
        $this->assertEquals(0, count($this->repo->findAll()));
    }

    public function testRepository()
    {
        $seriesType = new SeriesType();

        $name = "Series Type 1";
        $description = "Series Type description";
        $cod = "Cod_1";

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