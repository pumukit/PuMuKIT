<?php

namespace Pumukit\SchemaBundle\Tests\Other;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class MultimediaObjectRankTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $qb;
    private $factoryService;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository(MultimediaObject::class);
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');

        //DELETE DATABASE
        $this->dm->getDocumentCollection(MultimediaObject::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection(Series::class)
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

    public function testRank()
    {
        $series = $this->createSeries("Stark's growing pains");
        $otherSeries = $this->createSeries("Stark's growing pains");
        $this->dm->persist($series);
        $this->dm->persist($otherSeries);
        $this->dm->flush();

        $mm1 = $this->createMultimediaObjectAssignedToSeries('MmObject 1', $series);
        $mm2 = $this->createMultimediaObjectAssignedToSeries('MmObject 2', $series);
        $mm3 = $this->createMultimediaObjectAssignedToSeries('MmObject 3', $series);
        $mm4 = $this->createMultimediaObjectAssignedToSeries('MmObject 4', $series);
        $otherMm = $this->createMultimediaObjectAssignedToSeries('MmObject', $otherSeries);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->persist($mm4);
        $this->dm->persist($otherMm);
        $this->dm->flush();

        $this->assertEquals(1, $mm1->getRank());
        $this->assertEquals(2, $mm2->getRank());
        $this->assertEquals(3, $mm3->getRank());
        $this->assertEquals(4, $mm4->getRank());

        $mm1->setRank(2);

        $this->dm->persist($mm1);
        $this->dm->flush();

        $this->assertEquals(2, $mm1->getRank());
        $this->assertEquals(1, $mm2->getRank());
        $this->assertEquals(3, $mm3->getRank());
        $this->assertEquals(4, $mm4->getRank());

        $mm1->setRank(3);

        $this->dm->persist($mm1);
        $this->dm->flush();

        $this->assertEquals(3, $mm1->getRank());
        $this->assertEquals(1, $mm2->getRank());
        $this->assertEquals(2, $mm3->getRank());
        $this->assertEquals(4, $mm4->getRank());

        $mm1->setRank(4);

        $this->dm->persist($mm1);
        $this->dm->flush();

        $this->assertEquals(4, $mm1->getRank());
        $this->assertEquals(1, $mm2->getRank());
        $this->assertEquals(2, $mm3->getRank());
        $this->assertEquals(3, $mm4->getRank());

        $mm1->setRank(1);

        $this->dm->persist($mm1);
        $this->dm->flush();

        $this->assertEquals(1, $mm1->getRank());
        $this->assertEquals(2, $mm2->getRank());
        $this->assertEquals(3, $mm3->getRank());
        $this->assertEquals(4, $mm4->getRank());

        $mm1->setRank(-1);

        $this->dm->persist($mm1);
        $this->dm->flush();

        $this->assertEquals(4, $mm1->getRank());
        $this->assertEquals(1, $mm2->getRank());
        $this->assertEquals(2, $mm3->getRank());
        $this->assertEquals(3, $mm4->getRank());
    }

    private function createMultimediaObjectAssignedToSeries($title, Series $series)
    {
        $rank = 1;
        $status = MultimediaObject::STATUS_NEW;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $subtitle = 'Subtitle';
        $description = 'Description';
        $duration = 123;

        $mm = $this->factoryService->createMultimediaObject($series);

        $mm->setStatus($status);
        $mm->setRecordDate($record_date);
        $mm->setPublicDate($public_date);
        $mm->setTitle($title);
        $mm->setSubtitle($subtitle);
        $mm->setDescription($description);
        $mm->setDuration($duration);

        $this->dm->persist($mm);
        $this->dm->persist($series);
        $this->dm->flush();

        return $mm;
    }

    private function createSeries($title)
    {
        $subtitle = 'subtitle';
        $description = 'description';
        $test_date = new \DateTime('now');

        $series = $this->factoryService->createSeries();

        $series->setTitle($title);
        $series->setSubtitle($subtitle);
        $series->setDescription($description);
        $series->setPublicDate($test_date);

        $this->dm->persist($series);
        $this->dm->flush();

        return $series;
    }
}
