<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class SeriesRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $personService;
    private $factoryService;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository(Series::class);
        $this->personService = static::$kernel->getContainer()->get('pumukitschema.person');
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');

        $this->dm->getDocumentCollection(MultimediaObject::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection(Role::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection(Person::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection(Series::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection('PumukitSchemaBundle:SeriesType')
            ->remove([])
        ;
        $this->dm->getDocumentCollection(Tag::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection(Group::class)
            ->remove([])
        ;
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        $this->personService = null;
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
        $series = new Series();

        $title = 'Series title';
        $series->setTitle($title);

        $this->dm->persist($series);
        $this->dm->flush();

        $this->assertEquals(1, count($this->repo->findAll()));
        $this->assertEquals($series, $this->repo->find($series->getId()));

        $pic1 = new Pic();
        $pic1->setUrl('http://domain.com/pic1.png');

        $pic2 = new Pic();
        $pic2->setUrl('http://domain.com/pic2.png');

        $pic3 = new Pic();
        $pic3->setUrl('http://domain.com/pic3.png');

        $series->addPic($pic1);
        $series->addPic($pic2);
        $series->addPic($pic3);

        $this->dm->persist($series);
        $this->dm->flush();

        $this->assertEquals($pic1, $series->getPic());
        $this->assertEquals($pic2, $series->getPicById($pic2->getId()));
        $this->assertEquals(null, $series->getPicById(null));
    }

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
        $sort = [];
        $sortAsc = ['title' => 1];
        $sortDesc = ['title' => -1];

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
        $arrayAsc = [$series1, $series2, $series3];
        $arrayAscResult = array_values($this->repo->findWithTag($tag1, $sortAsc)->toArray());
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }
        $limit = 2;
        $page = 1;
        $arrayAsc = [$series3];
        $arrayAscResult = array_values($this->repo->findWithTag($tag1, $sortAsc, $limit, $page)->toArray());
        $this->assertEquals(1, $this->repo->findWithTag($tag1, $sortAsc, $limit, $page)->count(true));
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }

        $arrayDesc = [$series3, $series2, $series1];
        $arrayDescResult = array_values($this->repo->findWithTag($tag1, $sortDesc)->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }
        $limit = 2;
        $page = 1;
        $arrayDesc = [$series1];
        $arrayDescResult = array_values($this->repo->findWithTag($tag1, $sortDesc, $limit, $page)->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }

        // FIND ONE SERIES WITH TAG
        $this->assertEquals($series1, $this->repo->findOneWithTag($tag2));
        $this->assertEquals($series3, $this->repo->findOneWithTag($tag3));

        // FIND SERIES WITH ANY TAG
        $arrayTags = [$tag1, $tag2];
        $this->assertEquals(3, $this->repo->findWithAnyTag($arrayTags)->count(true));
        $limit = 2;
        $this->assertEquals(2, $this->repo->findWithAnyTag($arrayTags, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(2, $this->repo->findWithAnyTag($arrayTags, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithAnyTag($arrayTags, $sort, $limit, $page)->count(true));

        $arrayTags = [$tag3];
        $this->assertEquals(1, $this->repo->findWithAnyTag($arrayTags)->count(true));

        // FIND SERIES WITH ANY TAG (SORT)
        $arrayTags = [$tag1, $tag2];
        $arrayAsc = [$series1, $series2, $series3];
        $query = $this->repo->findWithAnyTag($arrayTags, $sortAsc);
        $arrayAscResult = array_values($query->toArray());
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }
        $limit = 2;
        $arrayAsc = [$series1, $series2];
        $query = $this->repo->findWithAnyTag($arrayTags, $sortAsc, $limit);
        $arrayAscResult = array_values($query->toArray());
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }

        $arrayDesc = [$series3, $series2, $series1];
        $query = $this->repo->findWithAnyTag($arrayTags, $sortDesc);
        $arrayDescResult = array_values($query->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }
        $limit = 2;
        $arrayDesc = [$series3, $series2];
        $query = $this->repo->findWithAnyTag($arrayTags, $sortDesc, $limit);
        $arrayDescResult = array_values($query->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }

        // FIND SERIES WITH ALL TAGS
        $arrayTags = [$tag1, $tag2];
        $this->assertEquals(2, $this->repo->findWithAllTags($arrayTags)->count(true));
        $limit = 1;
        $this->assertEquals(1, $this->repo->findWithAllTags($arrayTags, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(1, $this->repo->findWithAllTags($arrayTags, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithAllTags($arrayTags, $sort, $limit, $page)->count(true));

        $arrayTags = [$tag2, $tag3];
        $this->assertEquals(1, $this->repo->findWithAllTags($arrayTags)->count(true));

        // FIND SERIES WITH ALL TAGS (SORT)
        $arrayTags = [$tag1, $tag2];
        $arrayAsc = [$series1, $series2];
        $query = $this->repo->findWithAllTags($arrayTags, $sortAsc);
        $arrayAscResult = array_values($query->toArray());
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }
        $limit = 1;
        $page = 1;
        $arrayAsc = [$series2];
        $query = $this->repo->findWithAllTags($arrayTags, $sortAsc, $limit, $page);
        $arrayAscResult = array_values($query->toArray());
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }

        $arrayDesc = [$series2, $series1];
        $query = $this->repo->findWithAllTags($arrayTags, $sortDesc);
        $arrayDescResult = array_values($query->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }
        $limit = 1;
        $page = 1;
        $arrayDesc = [$series1];
        $query = $this->repo->findWithAllTags($arrayTags, $sortDesc, $limit, $page);
        $arrayDescResult = array_values($query->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }

        // FIND ONE SERIES WITH ALL TAGS
        $arrayTags = [$tag1, $tag2];
        $this->assertEquals($series1, $this->repo->findOneWithAllTags($arrayTags));

        $arrayTags = [$tag2, $tag3];
        $this->assertEquals($series3, $this->repo->findOneWithAllTags($arrayTags));

        // FIND SERIES WITHOUT TAG
        $this->assertEquals(2, $this->repo->findWithoutTag($tag3)->count(true));
        $limit = 1;
        $this->assertEquals(1, $this->repo->findWithoutTag($tag3, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(1, $this->repo->findWithoutTag($tag3, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithoutTag($tag3, $sort, $limit, $page)->count(true));

        // FIND SERIES WITHOUT TAG (SORT)
        $arrayAsc = [$series1, $series2];
        $query = $this->repo->findWithoutTag($tag3, $sortAsc);
        $arrayAscResult = array_values($query->toArray());
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }
        $limit = 1;
        $page = 1;
        $arrayAsc = [$series2];
        $query = $this->repo->findWithoutTag($tag3, $sortAsc, $limit, $page);
        $arrayAscResult = array_values($query->toArray());
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }

        $arrayDesc = [$series2, $series1];
        $query = $this->repo->findWithoutTag($tag3, $sortDesc);
        $arrayDescResult = array_values($query->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }
        $limit = 1;
        $page = 1;
        $arrayDesc = [$series1];
        $query = $this->repo->findWithoutTag($tag3, $sortDesc, $limit, $page);
        $arrayDescResult = array_values($query->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }

        // FIND ONE SERIES WITHOUT TAG
        $this->assertEquals($series1, $this->repo->findOneWithoutTag($tag3));

        // FIND SERIES WITHOUT ALL TAGS
        $mm11->addTag($tag3);
        $mm12->addTag($tag3);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->flush();

        $arrayTags = [$tag1, $tag2, $tag3];
        $this->assertEquals(2, $this->repo->findWithoutAllTags($arrayTags)->count(true));
        $limit = 1;
        $this->assertEquals(1, $this->repo->findWithoutAllTags($arrayTags, $sort, $limit)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithoutAllTags($arrayTags, $sort, $limit, $page)->count(true));

        // FIND SERIES WITHOUT ALL TAGS (SORT)
        $arrayAsc = [$series2, $series3];
        $query = $this->repo->findWithoutAllTags($arrayTags, $sortAsc);
        $arrayAscResult = array_values($query->toArray());
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }
        $limit = 1;
        $arrayAsc = [$series2];
        $query = $this->repo->findWithoutAllTags($arrayTags, $sortAsc, $limit);
        $arrayAscResult = array_values($query->toArray());
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }

        $arrayDesc = [$series3, $series2];
        $query = $this->repo->findWithoutAllTags($arrayTags, $sortDesc);
        $arrayDescResult = array_values($query->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }
        $limit = 1;
        $arrayDesc = [$series3];
        $query = $this->repo->findWithoutAllTags($arrayTags, $sortDesc, $limit);
        $arrayDescResult = array_values($query->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }
    }

    public function testCreateBuilderWithTag()
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

        $series1 = $this->createSeries('Series 1');
        $series2 = $this->createSeries('Series 2');
        $series3 = $this->createSeries('Series 3');

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm22 = $this->factoryService->createMultimediaObject($series2);
        $mm33 = $this->factoryService->createMultimediaObject($series3);

        $this->dm->persist($mm11);
        $this->dm->persist($mm22);
        $this->dm->persist($mm33);
        $this->dm->flush();

        $mm11->addTag($tag1);
        $mm11->addTag($tag2);

        $mm22->addTag($tag2);
        $mm22->addTag($tag3);

        $mm33->addTag($tag1);
        $mm33->addTag($tag3);

        $this->dm->persist($mm11);
        $this->dm->persist($mm22);
        $this->dm->persist($mm33);
        $this->dm->flush();

        // SORT
        $sort = [];
        $sortAsc = ['title' => 1];
        $sortDesc = ['title' => -1];

        $this->assertEquals(2, count($this->repo->createBuilderWithTag($tag1)->getQuery()->execute()));
        $this->assertEquals(2, count($this->repo->createBuilderWithTag($tag1, $sort)->getQuery()->execute()));
        $this->assertEquals(2, count($this->repo->createBuilderWithTag($tag2, $sortAsc)->getQuery()->execute()));
        $this->assertEquals(2, count($this->repo->createBuilderWithTag($tag3, $sortDesc)->getQuery()->execute()));
    }

    public function testFindByPicId()
    {
        $series1 = $this->factoryService->createSeries();
        $title1 = 'Series 1';
        $series1->setTitle($title1);

        $pic = new Pic();
        $this->dm->persist($pic);

        $series1->addPic($pic);

        $this->dm->persist($series1);
        $this->dm->flush();

        $this->assertEquals($series1, $this->repo->findByPicId($pic->getId()));
    }

    public function testFindSeriesByPersonId()
    {
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
        $this->assertEquals([$series1, $series2], array_values($seriesKate->toArray()));

        $seriesJohn = $this->repo->findSeriesByPersonId($personJohn->getId());
        $this->assertEquals(2, count($seriesJohn));
        $this->assertEquals([$series1, $series3], array_values($seriesJohn->toArray()));

        $seriesBob = $this->repo->findSeriesByPersonId($personBob->getId());
        $this->assertEquals(2, count($seriesBob));
        $this->assertEquals([$series1, $series3], array_values($seriesBob->toArray()));

        $seriesJohnActor = $this->repo->findByPersonIdAndRoleCod($personJohn->getId(), $roleActor->getCod());
        $seriesJohnPresenter = $this->repo->findByPersonIdAndRoleCod($personJohn->getId(), $rolePresenter->getCod());
        $seriesBobActor = $this->repo->findByPersonIdAndRoleCod($personBob->getId(), $roleActor->getCod());
        $seriesBobPresenter = $this->repo->findByPersonIdAndRoleCod($personBob->getId(), $rolePresenter->getCod());
        $seriesKateActor = $this->repo->findByPersonIdAndRoleCod($personKate->getId(), $roleActor->getCod());
        $seriesKatePresenter = $this->repo->findByPersonIdAndRoleCod($personKate->getId(), $rolePresenter->getCod());

        $this->assertEquals(2, count($seriesJohnActor));
        $this->assertTrue(in_array($series1, $seriesJohnActor->toArray()));
        $this->assertFalse(in_array($series2, $seriesJohnActor->toArray()));
        $this->assertTrue(in_array($series3, $seriesJohnActor->toArray()));

        $this->assertEquals(2, count($seriesJohnPresenter));
        $this->assertTrue(in_array($series1, $seriesJohnPresenter->toArray()));
        $this->assertFalse(in_array($series2, $seriesJohnPresenter->toArray()));
        $this->assertTrue(in_array($series3, $seriesJohnPresenter->toArray()));

        $this->assertEquals(2, count($seriesBobActor));
        $this->assertTrue(in_array($series1, $seriesBobActor->toArray()));
        $this->assertFalse(in_array($series2, $seriesBobActor->toArray()));
        $this->assertTrue(in_array($series3, $seriesBobActor->toArray()));

        $this->assertEquals(1, count($seriesBobPresenter));
        $this->assertTrue(in_array($series1, $seriesBobPresenter->toArray()));
        $this->assertFalse(in_array($series2, $seriesBobPresenter->toArray()));
        $this->assertFalse(in_array($series3, $seriesBobPresenter->toArray()));

        $this->assertEquals(2, count($seriesKateActor));
        $this->assertTrue(in_array($series1, $seriesKateActor->toArray()));
        $this->assertTrue(in_array($series2, $seriesKateActor->toArray()));
        $this->assertFalse(in_array($series3, $seriesKateActor->toArray()));

        $this->assertEquals(1, count($seriesKatePresenter));
        $this->assertFalse(in_array($series1, $seriesKatePresenter->toArray()));
        $this->assertTrue(in_array($series2, $seriesKatePresenter->toArray()));
        $this->assertFalse(in_array($series3, $seriesKatePresenter->toArray()));

        $group1 = new Group();
        $group1->setKey('group1');
        $group1->setName('Group 1');
        $group2 = new Group();
        $group2->setKey('group2');
        $group2->setName('Group 2');
        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->flush();
        $mm21->addGroup($group1);
        $this->dm->persist($mm21);
        $this->dm->flush();

        $groups = [$group1->getId()];
        $seriesJohnActor1 = $this->repo->findByPersonIdAndRoleCodOrGroups($personJohn->getId(), $roleActor->getCod(), $groups);
        $groups = [$group2->getId()];
        $seriesJohnActor2 = $this->repo->findByPersonIdAndRoleCodOrGroups($personJohn->getId(), $roleActor->getCod(), $groups);

        $this->assertEquals(3, count($seriesJohnActor1));
        $this->assertTrue(in_array($series1, $seriesJohnActor1->toArray()));
        $this->assertTrue(in_array($series2, $seriesJohnActor1->toArray()));
        $this->assertTrue(in_array($series3, $seriesJohnActor1->toArray()));

        $this->assertEquals(2, count($seriesJohnActor2));
        $this->assertTrue(in_array($series1, $seriesJohnActor2->toArray()));
        $this->assertFalse(in_array($series2, $seriesJohnActor2->toArray()));
        $this->assertTrue(in_array($series3, $seriesJohnActor2->toArray()));
    }

    public function testFindBySeriesType()
    {
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
    }

    public function testSimpleMultimediaObjectsInSeries()
    {
        $series1 = $this->createSeries('Series 1');

        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $mm13 = $this->factoryService->createMultimediaObject($series1);

        $this->assertEquals(3, count($this->repo->getMultimediaObjects($series1)));
    }

    public function testMultimediaObjectsInSeries()
    {
        $this->markTestSkipped('S');

        $series1 = $this->createSeries('Series 1');
        $series2 = $this->createSeries('Series 2');

        // NOTE: After creation we must take the initialized document
        $series1 = $this->repo->find($this->repo->getId());
        $series2 = $this->repo->find($series2->getId());

        $this->assertEquals(0, count($this->repo->getMultimediaObjects($series1)));
        $this->assertEquals(0, count($this->repo->getMultimediaObjects($series2)));

        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $mm13 = $this->factoryService->createMultimediaObject($series1);

        $mm21 = $this->factoryService->createMultimediaObject($series2);
        $mm22 = $this->factoryService->createMultimediaObject($series2);

        $this->assertEquals(3, count($this->repo->getMultimediaObjects($series1)));
        $this->assertEquals(2, count($this->repo->getMultimediaObjects($series2)));

        $this->dm->remove($mm11);
        $this->dm->flush();

        $this->assertEquals(2, count($this->repo->getMultimediaObjects($series1)));
        $this->assertEquals(2, count($this->repo->getMultimediaObjects($series2)));

        $this->assertTrue($series1->containsMultimediaObject($mm12));
        $this->assertFalse($series1->containsMultimediaObject($mm11));
    }

    public function testRankInAddMultimediaObject()
    {
        $series1 = $this->createSeries('Series 1');
        $this->assertEquals(0, count($this->repo->getMultimediaObjects($series1)));

        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $mm13 = $this->factoryService->createMultimediaObject($series1);
        $mm14 = $this->factoryService->createMultimediaObject($series1);
        $mm15 = $this->factoryService->createMultimediaObject($series1);

        $this->assertEquals(1, $mm11->getRank());
        $this->assertEquals(2, $mm12->getRank());
        $this->assertEquals(3, $mm13->getRank());
        $this->assertEquals(4, $mm14->getRank());
        $this->assertEquals(5, $mm15->getRank());

        $series2 = $this->createSeries('Series 2');
        $this->assertEquals(0, count($this->repo->getMultimediaObjects($series2)));

        $mm21 = $this->factoryService->createMultimediaObject($series2);
        $mm22 = $this->factoryService->createMultimediaObject($series2);
        $mm23 = $this->factoryService->createMultimediaObject($series2);
        $mm24 = $this->factoryService->createMultimediaObject($series2);
        $mm25 = $this->factoryService->createMultimediaObject($series2);

        $this->assertEquals(1, $mm21->getRank());
        $this->assertEquals(2, $mm22->getRank());
        $this->assertEquals(3, $mm23->getRank());
        $this->assertEquals(4, $mm24->getRank());
        $this->assertEquals(5, $mm25->getRank());
    }

    public function testPicsInSeries()
    {
        $series = $this->createSeries('Series');

        $pic1 = new Pic();
        $pic1->setUrl('http://domain.com/pic1.png');

        $pic2 = new Pic();
        $pic2->setUrl('http://domain.com/pic2.png');

        $pic3 = new Pic();
        $pic3->setUrl('http://domain.com/pic3.png');

        $series->addPic($pic1);
        $series->addPic($pic2);
        $series->addPic($pic3);

        $this->dm->persist($series);
        $this->dm->flush();

        $this->assertEquals(3, count($this->repo->find($series->getId())->getPics()));
        $this->assertEquals($pic2, $this->repo->find($series->getId())->getPicById($pic2->getId()));

        $arrayPics = [$pic1, $pic2, $pic3];
        $this->assertEquals($arrayPics, $this->repo->find($series->getId())->getPics()->toArray());

        $series->upPicById($pic2->getId());

        $this->dm->persist($series);
        $this->dm->flush();

        $arrayPics = [$pic2, $pic1, $pic3];
        $this->assertEquals($arrayPics, $this->repo->find($series->getId())->getPics()->toArray());

        $series->downPicById($pic1->getId());

        $this->dm->persist($series);
        $this->dm->flush();

        $arrayPics = [$pic2, $pic3, $pic1];
        $this->assertEquals($arrayPics, $this->repo->find($series->getId())->getPics()->toArray());

        $this->assertTrue($series->containsPic($pic3));

        $series->removePicById($pic3->getId());

        $this->assertFalse($series->containsPic($pic3));
    }

    public function testFindWithTagAndSeriesType()
    {
        $seriesType1 = new SeriesType();
        $seriesType1->setName('Series Type 1');
        $seriesType2 = new SeriesType();
        $seriesType2->setName('Series Type 2');

        $this->dm->persist($seriesType1);
        $this->dm->persist($seriesType2);

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

        $series1 = $this->createSeries('Series 1');
        $series1->setSeriesType($seriesType1);
        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $mm13 = $this->factoryService->createMultimediaObject($series1);

        $series2 = $this->createSeries('Series 2');
        $series2->setSeriesType($seriesType1);
        $mm21 = $this->factoryService->createMultimediaObject($series2);
        $mm22 = $this->factoryService->createMultimediaObject($series2);
        $mm23 = $this->factoryService->createMultimediaObject($series2);

        $series3 = $this->createSeries('Series 3');
        $series3->setSeriesType($seriesType2);
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
        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $this->assertEquals(2, count($this->repo->findWithTagAndSeriesType($tag1, $seriesType1)));
        $this->assertEquals(2, count($this->repo->findWithTagAndSeriesType($tag2, $seriesType1)));
        $this->assertEquals(0, count($this->repo->findWithTagAndSeriesType($tag3, $seriesType1)));
        $this->assertEquals(1, count($this->repo->findWithTagAndSeriesType($tag1, $seriesType2)));
        $this->assertEquals(1, count($this->repo->findWithTagAndSeriesType($tag2, $seriesType2)));
        $this->assertEquals(1, count($this->repo->findWithTagAndSeriesType($tag3, $seriesType2)));
    }

    public function testFindOneBySeriesProperty()
    {
        $series1 = $this->createSeries('Series 1');
        $series1->setProperty('dataexample', 'title1');

        $series2 = $this->createSeries('Series 2');
        $series2->setProperty('dataexample', 'title2');

        $series3 = $this->createSeries('Series 3');
        $series3->setProperty('dataexample', 'title3');

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $this->assertEquals($series1, $this->repo->findOneBySeriesProperty('dataexample', $series1->getProperty('dataexample')));
        $this->assertNull($this->repo->findOneBySeriesProperty('data', $series2->getProperty('dataexample')));
        $this->assertNull($this->repo->findOneBySeriesProperty('dataexample', $series3->getProperty('data')));
        $this->assertEquals($series3, $this->repo->findOneBySeriesProperty('dataexample', $series3->getProperty('dataexample')));
    }

    public function testCount()
    {
        $series1 = $this->createSeries('Series 1');
        $series2 = $this->createSeries('Series 2');
        $series3 = $this->createSeries('Series 3');

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $this->assertEquals(3, $this->repo->count());
    }

    public function testCountPublic()
    {
        $series1 = $this->createSeries('Series 1');
        $series2 = $this->createSeries('Series 2');
        $series3 = $this->createSeries('Series 3');

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $mm = $this->createMultimediaObjectAssignedToSeries('mm_public1', $series1);
        $mm2 = $this->createMultimediaObjectAssignedToSeries('mm_public2', $series2);

        $this->assertEquals(2, $this->repo->countPublic());
    }

    public function testFindByEmbeddedBroadcastType()
    {
        $mm1 = new MultimediaObject();
        $mm1->setTitle('test2');
        $this->dm->persist($mm1);
        $this->dm->flush();

        $mm2 = new MultimediaObject();
        $mm2->setTitle('test1');
        $this->dm->persist($mm2);
        $this->dm->flush();

        $type1 = EmbeddedBroadcast::TYPE_PASSWORD;
        $name1 = EmbeddedBroadcast::NAME_PASSWORD;
        $password1 = '123456';

        $embeddedBroadcast1 = new EmbeddedBroadcast();
        $embeddedBroadcast1->setType($type1);
        $embeddedBroadcast1->setName($name1);
        $embeddedBroadcast1->setPassword($password1);

        $type2 = EmbeddedBroadcast::TYPE_PUBLIC;
        $name2 = EmbeddedBroadcast::NAME_PUBLIC;
        $password2 = '123456';

        $embeddedBroadcast2 = new EmbeddedBroadcast();
        $embeddedBroadcast2->setType($type2);
        $embeddedBroadcast2->setName($name2);
        $embeddedBroadcast2->setPassword($password2);

        $mm1->setEmbeddedBroadcast($embeddedBroadcast1);
        $mm2->setEmbeddedBroadcast($embeddedBroadcast2);
        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->flush();

        $series1 = new Series();
        $series1->setTitle('series1');
        $series2 = new Series();
        $series2->setTitle('series2');
        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->flush();

        $mm1->setSeries($series1);
        $mm2->setSeries($series2);
        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->flush();

        $passwordSeries = $this->repo->findByEmbeddedBroadcastType(EmbeddedBroadcast::TYPE_PASSWORD);
        $publicSeries = $this->repo->findByEmbeddedBroadcastType(EmbeddedBroadcast::TYPE_PUBLIC);
        $loginSeries = $this->repo->findByEmbeddedBroadcastType(EmbeddedBroadcast::TYPE_LOGIN);
        $groupsSeries = $this->repo->findByEmbeddedBroadcastType(EmbeddedBroadcast::TYPE_GROUPS);
        $this->assertEquals(1, count($passwordSeries));
        $this->assertEquals(1, count($publicSeries));
        $this->assertEquals(0, count($loginSeries));
        $this->assertEquals(0, count($groupsSeries));

        $this->assertTrue(in_array($series1, $passwordSeries->toArray()));
        $this->assertFalse(in_array($series1, $publicSeries->toArray()));
        $this->assertFalse(in_array($series1, $loginSeries->toArray()));
        $this->assertFalse(in_array($series1, $groupsSeries->toArray()));

        $this->assertFalse(in_array($series2, $passwordSeries->toArray()));
        $this->assertTrue(in_array($series2, $publicSeries->toArray()));
        $this->assertFalse(in_array($series2, $loginSeries->toArray()));
        $this->assertFalse(in_array($series2, $groupsSeries->toArray()));

        $group1 = new Group();
        $group1->setKey('group1');
        $group1->setName('Group 1');
        $group2 = new Group();
        $group2->setKey('group2');
        $group2->setName('Group 2');
        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->flush();
        $embeddedBroadcast1->setType(EmbeddedBroadcast::TYPE_GROUPS);
        $embeddedBroadcast1->setName(EmbeddedBroadcast::NAME_GROUPS);
        $embeddedBroadcast1->addGroup($group1);
        $this->dm->persist($mm1);
        $this->dm->flush();

        $groups1 = [$group1->getId()];
        $groups2 = [$group2->getId()];
        $groups12 = [$group1->getId(), $group2->getId()];

        $seriesGroups1 = $this->repo->findByEmbeddedBroadcastTypeAndGroups(EmbeddedBroadcast::TYPE_GROUPS, $groups1);
        $seriesGroups2 = $this->repo->findByEmbeddedBroadcastTypeAndGroups(EmbeddedBroadcast::TYPE_GROUPS, $groups2);
        $seriesGroups12 = $this->repo->findByEmbeddedBroadcastTypeAndGroups(EmbeddedBroadcast::TYPE_GROUPS, $groups12);
        $this->assertEquals(1, count($seriesGroups1));
        $this->assertEquals(0, count($seriesGroups2));
        $this->assertEquals(0, count($seriesGroups12));

        $this->assertTrue(in_array($series1, $seriesGroups1->toArray()));
        $this->assertFalse(in_array($series2, $seriesGroups1->toArray()));
        $this->assertFalse(in_array($series1, $seriesGroups2->toArray()));
        $this->assertFalse(in_array($series2, $seriesGroups2->toArray()));
        $this->assertFalse(in_array($series1, $seriesGroups12->toArray()));
        $this->assertFalse(in_array($series2, $seriesGroups12->toArray()));
    }

    public function testFindByPersonIdAndRoleCodOrGroupsSorted()
    {
        $series1 = $this->createSeries('Series 1');
        $series2 = $this->createSeries('Series 2');
        $series3 = $this->createSeries('Series 3');

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $person1 = $this->createPerson('Person 1');
        $person2 = $this->createPerson('Person 2');

        $role1 = $this->createRole('Role1');
        $role2 = $this->createRole('Role2');
        $role3 = $this->createRole('Role3');

        $mm1 = $this->createMultimediaObjectAssignedToSeries('MmObject 1', $series1);
        $mm2 = $this->createMultimediaObjectAssignedToSeries('MmObject 2', $series2);
        $mm3 = $this->createMultimediaObjectAssignedToSeries('MmObject 3', $series1);
        $mm4 = $this->createMultimediaObjectAssignedToSeries('MmObject 4', $series3);

        $mm1->addPersonWithRole($person1, $role1);
        $mm2->addPersonWithRole($person2, $role2);
        $mm3->addPersonWithRole($person1, $role1);
        $mm3->addPersonWithRole($person2, $role2);
        $mm4->addPersonWithRole($person1, $role3);

        $group1 = new Group();
        $group1->setKey('group1');
        $group1->setName('Group 1');
        $group2 = new Group();
        $group2->setKey('group2');
        $group2->setName('Group 2');
        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->flush();
        $mm1->addGroup($group1);
        $mm2->addGroup($group1);
        $mm3->addGroup($group1);
        $mm3->addGroup($group2);
        $mm4->addGroup($group2);
        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->persist($mm4);
        $this->dm->flush();

        $groups1 = [$group1->getId()];
        $groups2 = [$group2->getId()];

        $seriesPerson1Role1Group1 = $this->repo->findByPersonIdAndRoleCodOrGroupsSorted($person1->getId(), $role1->getCod(), $groups1);
        $seriesPerson1Role1Group2 = $this->repo->findByPersonIdAndRoleCodOrGroupsSorted($person1->getId(), $role1->getCod(), $groups2);
        $seriesPerson1Role2Group1 = $this->repo->findByPersonIdAndRoleCodOrGroupsSorted($person1->getId(), $role2->getCod(), $groups1);
        $seriesPerson1Role2Group2 = $this->repo->findByPersonIdAndRoleCodOrGroupsSorted($person1->getId(), $role2->getCod(), $groups2);
        $seriesPerson1Role3Group1 = $this->repo->findByPersonIdAndRoleCodOrGroupsSorted($person1->getId(), $role3->getCod(), $groups1);
        $seriesPerson1Role3Group2 = $this->repo->findByPersonIdAndRoleCodOrGroupsSorted($person1->getId(), $role3->getCod(), $groups2);
        $seriesPerson2Role1Group1 = $this->repo->findByPersonIdAndRoleCodOrGroupsSorted($person2->getId(), $role1->getCod(), $groups1);
        $seriesPerson2Role1Group2 = $this->repo->findByPersonIdAndRoleCodOrGroupsSorted($person2->getId(), $role1->getCod(), $groups2);
        $seriesPerson2Role2Group1 = $this->repo->findByPersonIdAndRoleCodOrGroupsSorted($person2->getId(), $role2->getCod(), $groups1);
        $seriesPerson2Role2Group2 = $this->repo->findByPersonIdAndRoleCodOrGroupsSorted($person2->getId(), $role2->getCod(), $groups2);
        $seriesPerson2Role3Group1 = $this->repo->findByPersonIdAndRoleCodOrGroupsSorted($person2->getId(), $role3->getCod(), $groups1);
        $seriesPerson2Role3Group2 = $this->repo->findByPersonIdAndRoleCodOrGroupsSorted($person2->getId(), $role3->getCod(), $groups2);

        $this->assertEquals(2, count($seriesPerson1Role1Group1));
        $this->assertEquals(2, count($seriesPerson1Role1Group2));
        $this->assertEquals(2, count($seriesPerson1Role2Group1));
        $this->assertEquals(2, count($seriesPerson1Role2Group2));
        $this->assertEquals(3, count($seriesPerson1Role3Group1));
        $this->assertEquals(2, count($seriesPerson1Role3Group2));
        $this->assertEquals(2, count($seriesPerson2Role1Group1));
        $this->assertEquals(2, count($seriesPerson2Role1Group2));
        $this->assertEquals(2, count($seriesPerson2Role2Group1));
        $this->assertEquals(3, count($seriesPerson2Role2Group2));
        $this->assertEquals(2, count($seriesPerson2Role3Group1));
        $this->assertEquals(2, count($seriesPerson2Role3Group2));

        $this->assertTrue(in_array($series1, $seriesPerson1Role1Group1->toArray()));
        $this->assertTrue(in_array($series1, $seriesPerson1Role1Group2->toArray()));
        $this->assertTrue(in_array($series1, $seriesPerson1Role2Group1->toArray()));
        $this->assertTrue(in_array($series1, $seriesPerson1Role2Group2->toArray()));
        $this->assertTrue(in_array($series1, $seriesPerson1Role3Group1->toArray()));
        $this->assertTrue(in_array($series1, $seriesPerson1Role3Group2->toArray()));
        $this->assertTrue(in_array($series1, $seriesPerson2Role1Group1->toArray()));
        $this->assertTrue(in_array($series1, $seriesPerson2Role1Group2->toArray()));
        $this->assertTrue(in_array($series1, $seriesPerson2Role2Group1->toArray()));
        $this->assertTrue(in_array($series1, $seriesPerson2Role2Group2->toArray()));
        $this->assertTrue(in_array($series1, $seriesPerson2Role3Group1->toArray()));
        $this->assertTrue(in_array($series1, $seriesPerson2Role3Group2->toArray()));

        $this->assertTrue(in_array($series2, $seriesPerson1Role1Group1->toArray()));
        $this->assertFalse(in_array($series2, $seriesPerson1Role1Group2->toArray()));
        $this->assertTrue(in_array($series2, $seriesPerson1Role2Group1->toArray()));
        $this->assertFalse(in_array($series2, $seriesPerson1Role2Group2->toArray()));
        $this->assertTrue(in_array($series2, $seriesPerson1Role3Group1->toArray()));
        $this->assertFalse(in_array($series2, $seriesPerson1Role3Group2->toArray()));
        $this->assertTrue(in_array($series2, $seriesPerson2Role1Group1->toArray()));
        $this->assertFalse(in_array($series2, $seriesPerson2Role1Group2->toArray()));
        $this->assertTrue(in_array($series2, $seriesPerson2Role2Group1->toArray()));
        $this->assertTrue(in_array($series2, $seriesPerson2Role2Group2->toArray()));
        $this->assertTrue(in_array($series2, $seriesPerson2Role3Group1->toArray()));
        $this->assertFalse(in_array($series2, $seriesPerson2Role3Group2->toArray()));

        $this->assertFalse(in_array($series3, $seriesPerson1Role1Group1->toArray()));
        $this->assertTrue(in_array($series3, $seriesPerson1Role1Group2->toArray()));
        $this->assertFalse(in_array($series3, $seriesPerson1Role2Group1->toArray()));
        $this->assertTrue(in_array($series3, $seriesPerson1Role2Group2->toArray()));
        $this->assertTrue(in_array($series3, $seriesPerson1Role3Group1->toArray()));
        $this->assertTrue(in_array($series3, $seriesPerson1Role3Group2->toArray()));
        $this->assertFalse(in_array($series3, $seriesPerson2Role1Group1->toArray()));
        $this->assertTrue(in_array($series3, $seriesPerson2Role1Group2->toArray()));
        $this->assertFalse(in_array($series3, $seriesPerson2Role2Group1->toArray()));
        $this->assertTrue(in_array($series3, $seriesPerson2Role2Group2->toArray()));
        $this->assertFalse(in_array($series3, $seriesPerson2Role3Group1->toArray()));
        $this->assertTrue(in_array($series3, $seriesPerson2Role3Group2->toArray()));
    }

    public function testfindByTitleWithLocale()
    {
        $test1 = 'test1';
        $test2 = 'test2';
        $prueba1 = 'prueba1';
        $prueba2 = 'prueba2';
        $enLocale = 'en';
        $esLocale = 'es';
        $series1I8nTitle = [$enLocale => $test1, $esLocale => $prueba1];
        $series1 = new Series();
        $series1->setI18nTitle($series1I8nTitle);

        $series2I8nTitle = [$enLocale => $test2, $esLocale => $prueba2];
        $series2 = new Series();
        $series2->setI18nTitle($series2I8nTitle);

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->flush();

        $seriesTest1En = $this->repo->findByTitleWithLocale($test1, $enLocale)->toArray();
        $seriesTest1Es = $this->repo->findByTitleWithLocale($test1, $esLocale)->toArray();
        $seriesPrueba1En = $this->repo->findByTitleWithLocale($prueba1, $enLocale)->toArray();
        $seriesPrueba1Es = $this->repo->findByTitleWithLocale($prueba1, $esLocale)->toArray();
        $seriesTest2En = $this->repo->findByTitleWithLocale($test2, $enLocale)->toArray();
        $seriesTest2Es = $this->repo->findByTitleWithLocale($test2, $esLocale)->toArray();
        $seriesPrueba2En = $this->repo->findByTitleWithLocale($prueba2, $enLocale)->toArray();
        $seriesPrueba2Es = $this->repo->findByTitleWithLocale($prueba2, $esLocale)->toArray();

        $this->assertTrue(in_array($series1, $seriesTest1En));
        $this->assertFalse(in_array($series1, $seriesTest1Es));
        $this->assertFalse(in_array($series1, $seriesPrueba1En));
        $this->assertTrue(in_array($series1, $seriesPrueba1Es));
        $this->assertFalse(in_array($series1, $seriesTest2En));
        $this->assertFalse(in_array($series1, $seriesTest2Es));
        $this->assertFalse(in_array($series1, $seriesPrueba2En));
        $this->assertFalse(in_array($series1, $seriesPrueba2Es));

        $this->assertFalse(in_array($series2, $seriesTest1En));
        $this->assertFalse(in_array($series2, $seriesTest1Es));
        $this->assertFalse(in_array($series2, $seriesPrueba1En));
        $this->assertFalse(in_array($series2, $seriesPrueba1Es));
        $this->assertTrue(in_array($series2, $seriesTest2En));
        $this->assertFalse(in_array($series2, $seriesTest2Es));
        $this->assertFalse(in_array($series2, $seriesPrueba2En));
        $this->assertTrue(in_array($series2, $seriesPrueba2Es));
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
        $test_date = new \DateTime('now');

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
        $status = MultimediaObject::STATUS_PUBLISHED;
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

        return $mm;
    }

    private function createPerson($name)
    {
        $email = $name.'@mail.es';
        $web = 'http://www.url.com';
        $phone = '+34986123456';
        $honorific = 'honorific';
        $firm = 'firm';
        $post = 'post';
        $bio = 'Biografía extensa de la persona';

        $person = new Person();
        $person->setName($name);
        $person->setEmail($email);
        $person->setWeb($web);
        $person->setPhone($phone);
        $person->setHonorific($honorific);
        $person->setFirm($firm);
        $person->setPost($post);
        $person->setBio($bio);

        $this->dm->persist($person);
        $this->dm->flush();

        return $person;
    }

    private function createRole($name)
    {
        $cod = $name; // string (20)
        $rank = strlen($name); // Quick and dirty way to keep it unique
        $xml = '<xml content and definition of this/>';
        $display = true;
        $text = 'Black then white are all i see in my infancy.';

        $role = new Role();
        $role->setCod($cod);
        $role->setRank($rank);
        $role->setXml($xml);
        $role->setDisplay($display); // true by default
        $role->setName($name);
        $role->setText($text);
        $role->increaseNumberPeopleInMultimediaObject();

        $this->dm->persist($role);
        $this->dm->flush();

        return $role;
    }
}
