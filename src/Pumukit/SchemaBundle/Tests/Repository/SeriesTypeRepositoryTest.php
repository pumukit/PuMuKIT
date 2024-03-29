<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Repository;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\SeriesType;

/**
 * @internal
 *
 * @coversNothing
 */
class SeriesTypeRepositoryTest extends PumukitTestCase
{
    private $repo;
    private $factoryService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(SeriesType::class);
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        $this->factoryService = null;
        gc_collect_cycles();
    }

    public function testRepositoryEmpty()
    {
        static::assertCount(0, $this->repo->findAll());
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

        static::assertCount(1, $this->repo->findAll());
        static::assertEquals($seriesType, $this->repo->find($seriesType->getId()));
    }

    public function testContainsSeries()
    {
        static::markTestSkipped('S');

        $seriesType = new SeriesType();
        $this->dm->persist($seriesType);
        $this->dm->flush();

        $series = $this->factoryService->createSeries();
        $series->setSeriesType($seriesType);
        $this->dm->persist($series);
        $this->dm->persist($seriesType);
        $this->dm->flush();

        static::assertTrue($seriesType->containsSeries($series));
    }
}
