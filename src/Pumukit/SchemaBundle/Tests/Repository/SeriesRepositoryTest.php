<?php
namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Broadcast;

class SeriesRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $personService;
    private $factoryService;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();
        $this->dm = $kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository('PumukitSchemaBundle:Series');
        $this->personService = $kernel->getContainer()->get('pumukitschema.person');
        $this->factoryService = $kernel->getContainer()->get('pumukitschema.factory');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Role')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Person')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:SeriesType')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Broadcast')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Tag')
            ->remove(array());
        $this->dm->flush();
    }

    public function testRepositoryEmpty()
    {
        $this->assertEquals(0, count($this->repo->findAll()));
    }

    public function testRepository()
    {
        $series = new Series();

        $title = 'Series title';
        $series->setTitle($title);

        $this->dm->persist($series);
        $this->dm->flush();

        $this->assertEquals(1, count($this->repo->findAll()));
        $this->assertEquals($series, $this->repo->find($series->getId()));
    }

    // TO DO: test proper time sorting

    public function testFindSeriesWithTags()
    {
        $tag1 = new Tag();
        $tag1->setCod('tag1');
        $tag2 = new Tag();
        $tag2->setCod('tag2');
        $tag3 = new Tag();
        $tag3->setCod('tag3');
        
        $this->dm->persist($tag1);
        $this->dm->persist($tag2);
        $this->dm->persist($tag3);
        $this->dm->flush();

        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI);

        $series1 = $this->createSeries('Series 1');
        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $mm13 = $this->factoryService->createMultimediaObject($series1);

        $series2 = $this->createSeries('Series 2');
        $mm21 = $this->factoryService->createMultimediaObject($series2);
        $mm22 = $this->factoryService->createMultimediaObject($series2);
        $mm23 = $this->factoryService->createMultimediaObject($series2);

        $series3 = $this->createSeries('Series 3');
        $mm31 = $this->factoryService->createMultimediaObject($series3);
        $mm32 = $this->factoryService->createMultimediaObject($series3);
        $mm33 = $this->factoryService->createMultimediaObject($series3);
        $mm34 = $this->factoryService->createMultimediaObject($series3);

        $mm11->addTag($tag1);
        $mm11->addTag($tag2);

        $mm12->addTag($tag1);
        $mm12->addTag($tag2);

        $mm13->addTag($tag1);
        $mm13->addTag($tag2);

        $mm21->addTag($tag2);

        $mm22->addTag($tag1);
        $mm22->addTag($tag2);

        $mm23->addTag($tag1);

        $mm31->addTag($tag1);

        $mm32->addTag($tag2);
        $mm32->addTag($tag3);

        $mm33->addTag($tag1);

        $mm34->addTag($tag1);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm13);
        $this->dm->persist($mm21);
        $this->dm->persist($mm22);
        $this->dm->persist($mm23);
        $this->dm->persist($mm31);
        $this->dm->persist($mm32);
        $this->dm->persist($mm33);
        $this->dm->persist($mm34);
        $this->dm->flush();

        // SORT
        $sort = array();
        $sortAsc =  array('title' => 'asc');
        $sortDesc = array('title' => 'desc');

        // FIND SERIES WITH TAG
        $this->assertEquals(3, count($this->repo->findWithTag($tag1)));
        $limit = 2;
        $this->assertEquals(2, $this->repo->findWithTag($tag1, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(2, $this->repo->findWithTag($tag1, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithTag($tag1, $sort, $limit, $page)->count(true));

        $this->assertEquals(1, $this->repo->findWithTag($tag3)->count(true));

        // FIND SERIES WITH TAG (SORT)
        $arrayAsc = array($series1, $series2, $series3);
        $arrayAscResult = array_values($this->repo->findWithTag($tag1, $sortAsc)->toArray());
        $this->assertEquals($arrayAsc[0]->getTitle(), $arrayAscResult[0]->getTitle());
        $this->assertEquals($arrayAsc[1]->getTitle(), $arrayAscResult[1]->getTitle());
        $this->assertEquals($arrayAsc[2]->getTitle(), $arrayAscResult[2]->getTitle());
        $limit = 2;
        $page = 1;
        $arrayAsc = array($series3);
        $arrayAscResult = array_values($this->repo->findWithTag($tag1, $sortAsc, $limit, $page)->toArray());
        $this->assertEquals(1, $this->repo->findWithTag($tag1, $sortAsc, $limit, $page)->count(true));
        $this->assertEquals($arrayAsc[0]->getTitle(), $arrayAscResult[0]->getTitle());

        $arrayDesc = array($series3, $series2, $series1);
        $arrayDescResult = array_values($this->repo->findWithTag($tag1, $sortDesc)->toArray());
        $this->assertEquals($arrayDesc[0]->getTitle(), $arrayDescResult[0]->getTitle());
        $this->assertEquals($arrayDesc[1]->getTitle(), $arrayDescResult[1]->getTitle());
        $this->assertEquals($arrayDesc[2]->getTitle(), $arrayDescResult[2]->getTitle());
        $limit = 2;
        $page = 1;
        $arrayAsc = array($series1);
        $arrayAscResult = array_values($this->repo->findWithTag($tag1, $sortDesc, $limit, $page)->toArray());
        $this->assertEquals($arrayAsc[0]->getTitle(), $arrayAscResult[0]->getTitle());

        // FIND ONE SERIES WITH TAG
        $this->assertEquals(1, count($this->repo->findOneWithTag($tag2)));
        $this->assertEquals(1, count($this->repo->findOneWithTag($tag3)));
        $this->assertEquals($series3, $this->repo->findOneWithTag($tag3));

        // FIND SERIES WITH ANY TAG
        $arrayTags = array($tag1, $tag2);
        $this->assertEquals(3, $this->repo->findWithAnyTag($arrayTags)->count(true));
        $limit = 2;
        $this->assertEquals(2, $this->repo->findWithAnyTag($arrayTags, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(2, $this->repo->findWithAnyTag($arrayTags, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithAnyTag($arrayTags, $sort, $limit, $page)->count(true));

        $arrayTags = array($tag3);
        $this->assertEquals(1, $this->repo->findWithAnyTag($arrayTags)->count(true));

        // FIND SERIES WITH ALL TAGS
        $arrayTags = array($tag1, $tag2);
        $this->assertEquals(2, $this->repo->findWithAllTags($arrayTags)->count(true));
        $limit = 1;
        $this->assertEquals(1, $this->repo->findWithAllTags($arrayTags, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(1, $this->repo->findWithAllTags($arrayTags, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithAllTags($arrayTags, $sort, $limit, $page)->count(true));

        $arrayTags = array($tag2, $tag3);
        $this->assertEquals(1, $this->repo->findWithAllTags($arrayTags)->count(true));

        // FIND ONE SERIES WITH ALL TAGS
        $arrayTags = array($tag1, $tag2);
        $this->assertEquals(1, count($this->repo->findOneWithAllTags($arrayTags)));

        $arrayTags = array($tag2, $tag3);
        $this->assertEquals(1, count($this->repo->findOneWithAllTags($arrayTags)));
        $this->assertEquals($series3, $this->repo->findOneWithAllTags($arrayTags));

        // FIND SERIES WITHOUT TAG
        $this->assertEquals(2, $this->repo->findWithoutTag($tag3)->count(true));
        $limit = 1;
        $this->assertEquals(1, $this->repo->findWithoutTag($tag3, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(1, $this->repo->findWithoutTag($tag3, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithoutTag($tag3, $sort, $limit, $page)->count(true));

        // FIND ONE SERIES WITHOUT TAG
        $this->assertEquals(1, count($this->repo->findOneWithoutTag($tag3)));

    }

    public function testFindSeriesByPersonId()
    {
        $broadcast = new Broadcast();
        $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcast->setDefaultSel(true);
        $this->dm->persist($broadcast);
        $this->dm->flush();

        $series1 = $this->factoryService->createSeries();
        $title1 = 'Series 1';
        $series1->setTitle($title1);

        $series2 = $this->factoryService->createSeries();
        $title2 = 'Series 2';
        $series2->setTitle($title2);

        $series3 = $this->factoryService->createSeries();
        $title3 = 'Series 3';
        $series3->setTitle($title3);

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);

        $personJohn = new Person();
        $nameJohn = 'John Smith';
        $personJohn->setName($nameJohn);

        $personBob = new Person();
        $nameBob = 'Bob Clark';
        $personBob->setName($nameBob);

        $personKate = new Person();
        $nameKate = 'Kate Simmons';
        $personKate->setName($nameKate);

        $this->dm->persist($personJohn);
        $this->dm->persist($personBob);
        $this->dm->persist($personKate);

        $roleActor = new Role();
        $codActor = 'actor';
        $roleActor->setCod($codActor);

        $rolePresenter = new Role();
        $codPresenter = 'presenter';
        $rolePresenter->setCod($codPresenter);

        $this->dm->persist($roleActor);
        $this->dm->persist($rolePresenter);
        $this->dm->flush();

        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $title11 = 'Multimedia Object 11';
        $mm11->setTitle($title11);
        $mm11->addPersonWithRole($personJohn, $roleActor);
        $mm11->addPersonWithRole($personBob, $roleActor);
        $mm11->addPersonWithRole($personJohn, $rolePresenter);

        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $title12 = 'Multimedia Object 12';
        $mm12->setTitle($title12);
        $mm12->addPersonWithRole($personBob, $roleActor);
        $mm12->addPersonWithRole($personBob, $rolePresenter);

        $mm13 = $this->factoryService->createMultimediaObject($series1);
        $title13 = 'Multimedia Object 13';
        $mm13->setTitle($title13);
        $mm13->addPersonWithRole($personKate, $roleActor);

        $mm21 = $this->factoryService->createMultimediaObject($series2);
        $title21 = 'Multimedia Object 21';
        $mm21->setTitle($title21);
        $mm21->addPersonWithRole($personKate, $rolePresenter);
        $mm21->addPersonWithRole($personKate, $roleActor);

        $mm31 = $this->factoryService->createMultimediaObject($series3);
        $title31 = 'Multimedia Object 31';
        $mm31->setTitle($title31);
        $mm31->addPersonWithRole($personJohn, $rolePresenter);

        $mm32 = $this->factoryService->createMultimediaObject($series3);
        $title32 = 'Multimedia Object 3212312';
        $mm32->setTitle($title32);
        $mm32->addPersonWithRole($personJohn, $roleActor);
        $mm32->addPersonWithRole($personBob, $roleActor);
        $mm32->addPersonWithRole($personJohn, $rolePresenter);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm13);
        $this->dm->persist($mm21);
        $this->dm->persist($mm31);
        $this->dm->persist($mm32);
        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $seriesKate = $this->repo->findSeriesByPersonId($personKate->getId());
        $this->assertEquals(2, count($seriesKate));
        $this->assertEquals(array($series1, $series2), array_values($seriesKate->toArray()));

        $seriesJohn = $this->repo->findSeriesByPersonId($personJohn->getId());
        $this->assertEquals(2, count($seriesJohn));
        $this->assertEquals(array($series1, $series3), array_values($seriesJohn->toArray()));

        $seriesBob = $this->repo->findSeriesByPersonId($personBob->getId());
        $this->assertEquals(2, count($seriesBob));
        $this->assertEquals(array($series1, $series3), array_values($seriesBob->toArray()));
    }

    public function testFindBySeriesType()
    {
        $broadcast = new Broadcast();
        $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcast->setDefaultSel(true);
        $this->dm->persist($broadcast);
        $this->dm->flush();

        $seriesType1 = $this->createSeriesType('Series Type 1');
        $seriesType2 = $this->createSeriesType('Series Type 2');
        $seriesType3 = $this->createSeriesType('Series Type 3');

        $series1 = $this->factoryService->createSeries();
        $series2 = $this->factoryService->createSeries();
        $series3 = $this->factoryService->createSeries();
        $series4 = $this->factoryService->createSeries();
        $series5 = $this->factoryService->createSeries();

        $series1->setSeriesType($seriesType1);
        $series2->setSeriesType($seriesType1);
        $series3->setSeriesType($seriesType2);
        $series4->setSeriesType($seriesType3);
        $series5->setSeriesType($seriesType3);

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->persist($series4);
        $this->dm->persist($series5);
        $this->dm->persist($seriesType1);
        $this->dm->persist($seriesType2);
        $this->dm->persist($seriesType3);

        $this->dm->flush();

        $this->assertEquals(2, count($this->repo->findBySeriesType($seriesType1)));
        $this->assertEquals(1, count($this->repo->findBySeriesType($seriesType2)));
        $this->assertEquals(2, count($this->repo->findBySeriesType($seriesType3)));

        /*
        $this->assertEquals(2, count($seriesType1->getSeries()));
        $this->assertEquals(1, count($seriesType2->getSeries()));
        $this->assertEquals(2, count($seriesType3->getSeries()));
        $this->assertEquals(array($series1, $series2), $seriesType1->getSeries());
        $this->assertEquals(array($series3), $seriesType2->getSeries());
        $this->assertEquals(array($series4, $series5), $seriesType3->getSeries());
        */
    }

    private function createSeriesType($name)
    {
        $description = 'description';
        $series_type = new SeriesType();

        $series_type->setName($name);
        $series_type->setDescription($description);

        $this->dm->persist($series_type);
        $this->dm->flush();

        return $series_type;
    }

    private function createSeries($title)
    {
        $subtitle = 'subtitle';
        $description = 'description';
        $test_date = new \DateTime("now");

        $series = $this->factoryService->createSeries();

        $series->setTitle($title);
        $series->setSubtitle($subtitle);
        $series->setDescription($description);
        $series->setPublicDate($test_date);

        $this->dm->persist($series);

        return $series;
    }

    private function createMultimediaObjectAssignedToSeries($title, Series $series)
    {
        $status = MultimediaObject::STATUS_NORMAL;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $subtitle = 'Subtitle';
        $description = "Description";
        $duration = 123;

        $mm = $this->factoryService->createMultimediaObject($series);

        // $mm->addTag($tag1);
        // $mm->addTrack($track1);
        // $mm->addPic($pic1);
        // $mm->addMaterial($material1);

        $mm->setStatus($status);
        $mm->setRecordDate($record_date);
        $mm->setPublicDate($public_date);
        $mm->setTitle($title);
        $mm->setSubtitle($subtitle);
        $mm->setDescription($description);
        $mm->setDuration($duration);
        // $this->dm->persist($track1);
        // $this->dm->persist($pic1);
        // $this->dm->persist($material1);
        $this->dm->persist($mm);

        return $mm;
    }

    private function createBroadcast($broadcastTypeId)
    {
        $broadcast = new Broadcast();
        $broadcast->setName(ucfirst($broadcastTypeId));
        $broadcast->setBroadcastTypeId($broadcastTypeId);
        $broadcast->setPasswd('password');
        if (0 === strcmp(Broadcast::BROADCAST_TYPE_PRI, $broadcastTypeId)) {
            $broadcast->setDefaultSel(true);
        } else {
            $broadcast->setDefaultSel(false);
        }
        $broadcast->setDescription(ucfirst($broadcastTypeId).' broadcast');

        $this->dm->persist($broadcast);
        $this->dm->flush();

        return $broadcast;
    }
}
