<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\Link;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\Tag;

class MultimediaObjectRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $qb;
    private $factoryService;
    private $mmsPicService;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();
        $this->dm = $kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->factoryService = $kernel->getContainer()
            ->get('pumukitschema.factory');
        $this->mmsPicService = $kernel->getContainer()
            ->get('pumukitschema.mmspic');
    }

    public function setUp()
    {
        //DELETE DATABASE
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
        //$rank = 1;
        $status = MultimediaObject::STATUS_NORMAL;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $title = 'titulo cualquiera';
        $subtitle = 'Subtitle paragraph';
        $description = "Description text";
        $duration = 300;
        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI);

        $mmobj = new MultimediaObject();
        //$mmobj->setRank($rank);
        $mmobj->setStatus($status);
        $mmobj->setRecordDate($record_date);
        $mmobj->setPublicDate($public_date);
        $mmobj->setTitle($title);
        $mmobj->setSubtitle($subtitle);
        $mmobj->setDescription($description);
        $mmobj->setDuration($duration);
        $mmobj->setBroadcast($broadcast);

        $this->dm->persist($mmobj);
        $this->dm->flush();

        $this->assertEquals(1, count($this->repo->findAll()));
    }

    public function testCreateMultimediaObjectAndFindByCriteria()
    {
        $broadcast = new Broadcast();
        $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcast->setDefaultSel(true);
        $this->dm->persist($broadcast);
        $this->dm->flush();

        $series_type = $this->createSeriesType("Medieval Fantasy Sitcom");

        $series_main = $this->createSeries("Stark's growing pains");
        $series_wall = $this->createSeries("The Wall");
        $series_lhazar = $this->createSeries("A quiet life");

        $series_main->setSeriesType($series_type);
        $series_wall->setSeriesType($series_type);
        $series_lhazar->setSeriesType($series_type);

        $this->dm->persist($series_main);
        $this->dm->persist($series_wall);
        $this->dm->persist($series_lhazar);
        $this->dm->persist($series_type);
        $this->dm->flush();

        $person_ned = $this->createPerson('Ned');
        $person_benjen = $this->createPerson('Benjen');

        $role_lord = $this->createRole("Lord");
        $role_ranger = $this->createRole("Ranger");
        $role_hand = $this->createRole("Hand");

        $mm1 = $this->createMultimediaObjectAssignedToSeries('MmObject 1', $series_main);
        $mm2 = $this->createMultimediaObjectAssignedToSeries('MmObject 2', $series_wall);
        $mm3 = $this->createMultimediaObjectAssignedToSeries('MmObject 3', $series_main);
        $mm4 = $this->createMultimediaObjectAssignedToSeries('MmObject 4', $series_lhazar);

        $mm1->addPersonWithRole($person_ned, $role_lord);
        $mm2->addPersonWithRole($person_benjen, $role_ranger);
        $mm3->addPersonWithRole($person_ned, $role_lord);
        $mm3->addPersonWithRole($person_benjen, $role_ranger);
        $mm4->addPersonWithRole($person_ned, $role_hand);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->persist($mm4);
        $this->dm->flush();
        // DB setup END.

        // Test find by person
        $mmobj_ned = $this->repo->findByPersonId($person_ned->getId());
        $this->assertEquals(3, count($mmobj_ned));

        // Test find by person and role
        $mmobj_benjen_ranger = $this->repo->findByPersonIdWithRoleCod($person_benjen->getId(), $role_ranger->getCod());
        $mmobj_ned_lord = $this->repo->findByPersonIdWithRoleCod($person_ned->getId(), $role_lord->getCod());
        $mmobj_ned_hand = $this->repo->findByPersonIdWithRoleCod($person_ned->getId(), $role_hand->getCod());
        $mmobj_benjen_lord = $this->repo->findByPersonIdWithRoleCod($person_benjen->getId(), $role_lord->getCod());
        $mmobj_ned_ranger = $this->repo->findByPersonIdWithRoleCod($person_ned->getId(), $role_ranger->getCod());
        $mmobj_benjen_hand = $this->repo->findByPersonIdWithRoleCod($person_benjen->getId(), $role_hand->getCod());

        $this->assertEquals(2, count($mmobj_benjen_ranger));
        $this->assertEquals(2, count($mmobj_ned_lord));
        $this->assertEquals(1, count($mmobj_ned_hand));

        // TODO - FAILS #6100
        //$this->assertEquals(0, count($mmobj_benjen_lord));
        //$this->assertEquals(0, count($mmobj_ned_ranger));
        $this->assertEquals(0, count($mmobj_benjen_hand));

        $seriesBenjen = $this->repo->findSeriesFieldByPersonId($person_benjen->getId());
        $seriesNed = $this->repo->findSeriesFieldByPersonId($person_ned->getId());

        $this->assertEquals(2, count($seriesBenjen));
        $this->assertEquals($series_wall->getId(), $seriesBenjen->toArray()[0]);
        $this->assertEquals($series_main->getId(), $seriesBenjen->toArray()[1]);

        $this->assertEquals(2, count($seriesNed));
        $this->assertEquals($series_main->getId(), $seriesNed->toArray()[0]);
        $this->assertEquals($series_lhazar->getId(), $seriesNed->toArray()[1]);

    }

    public function testPeopleInMultimediaObjectCollection()
    {
        $personLucy = new Person();
        $personLucy->setName('Lucy');
        $personKate = new Person();
        $personKate->setName('Kate');
        $personPete = new Person();
        $personPete->setName('Pete');

        $roleActor = new Role();
        $roleActor->setCod('actor');
        $rolePresenter = new Role();
        $rolePresenter->setCod('presenter');
        $roleDirector = new Role();
        $roleDirector->setCod('director');

        $this->dm->persist($personLucy);
        $this->dm->persist($personKate);
        $this->dm->persist($personPete);

        $this->dm->persist($roleActor);
        $this->dm->persist($rolePresenter);
        $this->dm->persist($roleDirector);

        $mm = new MultimediaObject();
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($mm->containsPerson($personKate));
        $this->assertFalse($mm->containsPersonWithRole($personKate, $roleActor));
        $this->assertEquals(0, count($mm->getPeopleInMultimediaObject()));
        $this->assertFalse($mm->containsPersonWithAllRoles($personKate, array($roleActor, $rolePresenter, $roleDirector)));
        $this->assertFalse($mm->containsPersonWithAnyRole($personKate, array($roleActor, $rolePresenter, $roleDirector)));

        $mm->addPersonWithRole($personKate, $roleActor);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($mm->containsPerson($personKate));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $roleActor));
        $this->assertFalse($mm->containsPersonWithRole($personKate, $rolePresenter));
        $this->assertFalse($mm->containsPersonWithRole($personKate, $roleDirector));
        $this->assertFalse($mm->containsPerson($personLucy));
        $this->assertEquals(1, count($mm->getPeopleInMultimediaObject()));
        $this->assertEquals($personKate->getId(), $mm->getPersonWithRole($personKate, $roleActor)->getId());

        $mm2 = new MultimediaObject();
        $this->dm->persist($mm2);
        $this->dm->flush();

        $this->assertFalse($mm2->containsPerson($personKate));
        $this->assertFalse($mm2->containsPersonWithRole($personKate, $roleActor));
        $this->assertEquals(0, count($mm2->getPeopleInMultimediaObject()));

        $this->assertFalse($mm2->getPersonWithRole($personKate, $roleActor));

        $mm2->addPersonWithRole($personKate, $roleActor);
        $this->dm->persist($mm2);
        $this->dm->flush();

        $this->assertTrue($mm2->containsPerson($personKate));
        $this->assertTrue($mm2->containsPersonWithRole($personKate, $roleActor));
        $this->assertFalse($mm2->containsPersonWithRole($personKate, $rolePresenter));
        $this->assertFalse($mm2->containsPersonWithRole($personKate, $roleDirector));
        $this->assertFalse($mm2->containsPerson($personLucy));
        $this->assertEquals(1, count($mm2->getPeopleInMultimediaObject()));

        $mm->addPersonWithRole($personKate, $rolePresenter);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($mm->containsPersonWithRole($personKate, $roleActor));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $rolePresenter));
        $this->assertFalse($mm->containsPersonWithRole($personKate, $roleDirector));
        $this->assertEquals(1, count($mm->getPeopleInMultimediaObject()));

        $mm->addPersonWithRole($personKate, $roleDirector);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($mm->containsPersonWithRole($personKate, $roleActor));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $rolePresenter));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $roleDirector));
        $this->assertTrue($mm->containsPersonWithAllRoles($personKate, array($roleActor, $rolePresenter, $roleDirector)));
        $this->assertTrue($mm->containsPersonWithAnyRole($personKate, array($roleActor, $rolePresenter, $roleDirector)));
        $this->assertEquals(1, count($mm->getPeopleInMultimediaObject()));

        $mm->addPersonWithRole($personLucy, $roleDirector);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($mm->containsPersonWithRole($personKate, $roleActor));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $rolePresenter));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $roleDirector));
        $this->assertTrue($mm->containsPerson($personLucy));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $roleActor));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $rolePresenter));
        $this->assertTrue($mm->containsPersonWithRole($personLucy, $roleDirector));
        $this->assertEquals(2, count($mm->getPeopleInMultimediaObject()));

        $this->assertEquals(2, count($mm->getPeopleInMultimediaObjectByRole(null, false)));
        $mm->getEmbeddedRole($roleDirector)->setDisplay(false);
        $this->dm->persist($mm);
        $this->dm->flush();
        $this->assertEquals(2, count($mm->getPeopleInMultimediaObjectByRole(null, true)));
        $this->assertEquals(1, count($mm->getPeopleInMultimediaObjectByRole(null, false)));
        $mm->getEmbeddedRole($roleDirector)->setDisplay(true);
        $this->dm->persist($mm);
        $this->dm->flush();

        $peopleDirector = $mm->getPeopleInMultimediaObjectByRole($roleDirector);
        $this->assertEquals(array($personKate->getId(), $personLucy->getId()),
                            array($peopleDirector[0]->getId(), $peopleDirector[1]->getId()));

        $mm->downPersonWithRole($personKate, $roleDirector);
        $this->dm->persist($mm);
        $peopleDirector = $mm->getPeopleInMultimediaObjectByRole($roleDirector);
        $this->assertEquals(array($personLucy->getId(), $personKate->getId()),
                            array($peopleDirector[0]->getId(), $peopleDirector[1]->getId()));

        $mm->upPersonWithRole($personKate, $roleDirector);
        $this->dm->persist($mm);
        $peopleDirector = $mm->getPeopleInMultimediaObjectByRole($roleDirector);
        $this->assertEquals(array($personKate->getId(), $personLucy->getId()),
                            array($peopleDirector[0]->getId(), $peopleDirector[1]->getId()));

        $mm->upPersonWithRole($personLucy, $roleDirector);
        $this->dm->persist($mm);
        $peopleDirector = $mm->getPeopleInMultimediaObjectByRole($roleDirector);
        $this->assertEquals(array($personLucy->getId(), $personKate->getId()),
                            array($peopleDirector[0]->getId(), $peopleDirector[1]->getId()));

        $mm->downPersonWithRole($personLucy, $roleDirector);
        $this->dm->persist($mm);
        $peopleDirector = $mm->getPeopleInMultimediaObjectByRole($roleDirector);
        $this->assertEquals(array($personKate->getId(), $personLucy->getId()),
                            array($peopleDirector[0]->getId(), $peopleDirector[1]->getId()));

        $this->assertEquals(3, count($mm->getAllEmbeddedPeopleByPerson($personKate)));
        $this->assertEquals(1, count($mm->getAllEmbeddedPeopleByPerson($personLucy)));
        $this->assertEquals(1, count($mm2->getAllEmbeddedPeopleByPerson($personKate)));
        $this->assertEquals(0, count($mm2->getAllEmbeddedPeopleByPerson($personLucy)));

        $this->assertTrue($mm->removePersonWithRole($personKate, $roleActor));
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($mm->containsPersonWithRole($personKate, $roleActor));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $rolePresenter));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $roleDirector));
        $this->assertTrue($mm->containsPerson($personLucy));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $roleActor));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $rolePresenter));
        $this->assertTrue($mm->containsPersonWithRole($personLucy, $roleDirector));
        $this->assertEquals(2, count($mm->getPeopleInMultimediaObject()));

        $this->assertTrue($mm->removePersonWithRole($personLucy, $roleDirector));
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($mm->containsPersonWithRole($personKate, $roleActor));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $rolePresenter));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $roleDirector));
        $this->assertFalse($mm->containsPerson($personLucy));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $roleActor));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $rolePresenter));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $roleDirector));
        $this->assertEquals(1, count($mm->getPeopleInMultimediaObject()));

        $this->assertTrue($mm->removePersonWithRole($personKate, $roleDirector));
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($mm->containsPersonWithRole($personKate, $roleActor));
        $this->assertTrue($mm->containsPersonWithRole($personKate, $rolePresenter));
        $this->assertFalse($mm->containsPersonWithRole($personKate, $roleDirector));
        $this->assertFalse($mm->containsPerson($personLucy));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $roleActor));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $rolePresenter));
        $this->assertFalse($mm->containsPersonWithRole($personLucy, $roleDirector));
        $this->assertEquals(1, count($mm->getPeopleInMultimediaObject()));

        $this->assertFalse($mm->removePersonWithRole($personKate, $roleActor));
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertEquals(1, count($mm->getPeopleInMultimediaObject()));

        $this->assertTrue($mm->removePersonWithRole($personKate, $rolePresenter));
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertEquals(0, count($mm->getPeopleInMultimediaObject()));
    }

    public function testFindBySeries()
    {
        $this->assertEquals(0, count($this->repo->findAll()));

        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI);
        $series1 = $this->createSeries('Series 1');
        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $mm13 = $this->factoryService->createMultimediaObject($series1);

        $series2 = $this->createSeries('Series 2');
        $mm21 = $this->factoryService->createMultimediaObject($series2);
        $mm22 = $this->factoryService->createMultimediaObject($series2);

        $series1 = $this->dm->find('PumukitSchemaBundle:Series', $series1->getId());
        $series2 = $this->dm->find('PumukitSchemaBundle:Series', $series2->getId());

        $this->assertEquals(4, count($this->repo->findBySeries($series1)));
        $this->assertEquals(3, count($this->repo->findBySeries($series2)));
    }

    public function testFindWithStatus()
    {
        $broadcast = new Broadcast();
        $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcast->setDefaultSel(true);
        $this->dm->persist($broadcast);
        $this->dm->flush();

        $series = $this->createSeries('Serie prueba status');

        $mmNew = $this->createMultimediaObjectAssignedToSeries('Status new', $series);
        $mmNew->setStatus(MultimediaObject::STATUS_NEW);

        $mmHide = $this->createMultimediaObjectAssignedToSeries('Status hide', $series);
        $mmHide->setStatus(MultimediaObject::STATUS_HIDE);

        $mmBloq = $this->createMultimediaObjectAssignedToSeries('Status bloq', $series);
        $mmBloq->setStatus(MultimediaObject::STATUS_BLOQ);

        $mmNormal = $this->createMultimediaObjectAssignedToSeries('Status normal', $series);
        $mmNormal->setStatus(MultimediaObject::STATUS_NORMAL);

        $this->dm->persist($mmNew);
        $this->dm->persist($mmHide);
        $this->dm->persist($mmBloq);
        $this->dm->persist($mmNormal);
        $this->dm->flush();

        $this->assertEquals(1, count($this->repo->findWithStatus($series, array(MultimediaObject::STATUS_PROTOTYPE))));
        $this->assertEquals(1, count($this->repo->findWithStatus($series, array(MultimediaObject::STATUS_NEW))));
        $this->assertEquals(1, count($this->repo->findWithStatus($series, array(MultimediaObject::STATUS_HIDE))));
        $this->assertEquals(1, count($this->repo->findWithStatus($series, array(MultimediaObject::STATUS_BLOQ))));
        $this->assertEquals(1, count($this->repo->findWithStatus($series, array(MultimediaObject::STATUS_NORMAL))));
        $this->assertEquals(2, count($this->repo->findWithStatus($series, array(MultimediaObject::STATUS_PROTOTYPE, MultimediaObject::STATUS_NEW))));
        $this->assertEquals(3, count($this->repo->findWithStatus($series, array(MultimediaObject::STATUS_NORMAL, MultimediaObject::STATUS_NEW, MultimediaObject::STATUS_HIDE))));

        $mmArray = array($mmNew->getId() => $mmNew);
        $this->assertEquals($mmArray, $this->repo->findWithStatus($series, array(MultimediaObject::STATUS_NEW))->toArray());
        $mmArray = array($mmHide->getId() => $mmHide);
        $this->assertEquals($mmArray, $this->repo->findWithStatus($series, array(MultimediaObject::STATUS_HIDE))->toArray());
        $mmArray = array($mmBloq->getId() => $mmBloq);
        $this->assertEquals($mmArray, $this->repo->findWithStatus($series, array(MultimediaObject::STATUS_BLOQ))->toArray());
        $mmArray = array($mmNormal->getId() => $mmNormal);
        $this->assertEquals($mmArray, $this->repo->findWithStatus($series, array(MultimediaObject::STATUS_NORMAL))->toArray());
        $mmArray = array($mmNormal->getId() => $mmNormal, $mmNew->getId() => $mmNew, $mmHide->getId() => $mmHide);
        $this->assertEquals($mmArray, $this->repo->findWithStatus($series, array(MultimediaObject::STATUS_NORMAL, MultimediaObject::STATUS_NEW, MultimediaObject::STATUS_HIDE))->toArray());
    }

    public function testFindPrototype()
    {
        $broadcast = new Broadcast();
        $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcast->setDefaultSel(true);
        $this->dm->persist($broadcast);
        $this->dm->flush();

        $series = $this->createSeries('Serie prueba status');

        $mmNew = $this->createMultimediaObjectAssignedToSeries('Status new', $series);
        $mmNew->setStatus(MultimediaObject::STATUS_NEW);

        $mmHide = $this->createMultimediaObjectAssignedToSeries('Status hide', $series);
        $mmHide->setStatus(MultimediaObject::STATUS_HIDE);

        $mmBloq = $this->createMultimediaObjectAssignedToSeries('Status bloq', $series);
        $mmBloq->setStatus(MultimediaObject::STATUS_BLOQ);

        $mmNormal = $this->createMultimediaObjectAssignedToSeries('Status normal', $series);
        $mmNormal->setStatus(MultimediaObject::STATUS_NORMAL);

        $this->dm->persist($mmNew);
        $this->dm->persist($mmHide);
        $this->dm->persist($mmBloq);
        $this->dm->persist($mmNormal);
        $this->dm->flush();

        $this->assertEquals(1, count($this->repo->findPrototype($series)));
        $this->assertNotEquals($mmNew, $this->repo->findPrototype($series));
        $this->assertNotEquals($mmHide, $this->repo->findPrototype($series));
        $this->assertNotEquals($mmBloq, $this->repo->findPrototype($series));
        $this->assertNotEquals($mmNormal, $this->repo->findPrototype($series));
    }

    public function testFindWithoutPrototype()
    {
        $broadcast = new Broadcast();
        $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB);
        $broadcast->setDefaultSel(true);
        $this->dm->persist($broadcast);
        $this->dm->flush();

        $series = $this->createSeries('Serie prueba status');

        $mmNew = $this->createMultimediaObjectAssignedToSeries('Status new', $series);
        $mmNew->setStatus(MultimediaObject::STATUS_NEW);

        $mmHide = $this->createMultimediaObjectAssignedToSeries('Status hide', $series);
        $mmHide->setStatus(MultimediaObject::STATUS_HIDE);

        $mmBloq = $this->createMultimediaObjectAssignedToSeries('Status bloq', $series);
        $mmBloq->setStatus(MultimediaObject::STATUS_BLOQ);

        $mmNormal = $this->createMultimediaObjectAssignedToSeries('Status normal', $series);
        $mmNormal->setStatus(MultimediaObject::STATUS_NORMAL);

        $this->dm->persist($mmNew);
        $this->dm->persist($mmHide);
        $this->dm->persist($mmBloq);
        $this->dm->persist($mmNormal);
        $this->dm->flush();

        $this->assertEquals(4, count($this->repo->findWithoutPrototype($series)));

        $mmArray = array(
             $mmNew->getId() => $mmNew,
             $mmHide->getId() => $mmHide,
             $mmBloq->getId() => $mmBloq,
             $mmNormal->getId() => $mmNormal,
             );
        $this->assertEquals($mmArray, $this->repo->findWithoutPrototype($series)->toArray());
    }

    public function testEmbedPicsInMultimediaObject()
    {
        $pic1 = new Pic();
        $pic2 = new Pic();
        $pic3 = new Pic();

        $this->dm->persist($pic1);
        $this->dm->persist($pic2);
        $this->dm->persist($pic3);

        $mm = new MultimediaObject();
        $mm->addPic($pic1);
        $mm->addPic($pic2);
        $mm->addPic($pic3);

        $this->dm->persist($mm);

        $this->dm->flush();

        $this->assertEquals($pic1, $this->repo->find($mm->getId())->getPicById($pic1->getId()));
        $this->assertEquals($pic2, $this->repo->find($mm->getId())->getPicById($pic2->getId()));
        $this->assertEquals($pic3, $this->repo->find($mm->getId())->getPicById($pic3->getId()));
        $this->assertNull($this->repo->find($mm->getId())->getPicById(null));

        $mm->removePicById($pic2->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $picsArray = array($pic1, $pic3);
        $this->assertEquals(count($picsArray), count($this->repo->find($mm->getId())->getPics()));
        $this->assertEquals($picsArray, array_values($this->repo->find($mm->getId())->getPics()->toArray()));

        $mm->upPicById($pic3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $picsArray = array($pic3, $pic1);
        $this->assertEquals($picsArray, array_values($this->repo->find($mm->getId())->getPics()->toArray()));

        $mm->downPicById($pic3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $picsArray = array($pic1, $pic3);
        $this->assertEquals($picsArray, array_values($this->repo->find($mm->getId())->getPics()->toArray()));
    }

    public function testEmbedMaterialsInMultimediaObject()
    {
        $material1 = new Material();
        $material2 = new Material();
        $material3 = new Material();

        $this->dm->persist($material1);
        $this->dm->persist($material2);
        $this->dm->persist($material3);

        $mm = new MultimediaObject();
        $mm->addMaterial($material1);
        $mm->addMaterial($material2);
        $mm->addMaterial($material3);

        $this->dm->persist($mm);

        $this->dm->flush();

        $this->assertEquals($material1, $this->repo->find($mm->getId())->getMaterialById($material1->getId()));
        $this->assertEquals($material2, $this->repo->find($mm->getId())->getMaterialById($material2->getId()));
        $this->assertEquals($material3, $this->repo->find($mm->getId())->getMaterialById($material3->getId()));
        $this->assertNull($this->repo->find($mm->getId())->getMaterialById(null));

        $mm->removeMaterialById($material2->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $materialsArray = array($material1, $material3);
        $this->assertEquals(count($materialsArray), count($this->repo->find($mm->getId())->getMaterials()));
        $this->assertEquals($materialsArray, array_values($this->repo->find($mm->getId())->getMaterials()->toArray()));

        $mm->upMaterialById($material3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $materialsArray = array($material3, $material1);
        $this->assertEquals($materialsArray, array_values($this->repo->find($mm->getId())->getMaterials()->toArray()));

        $mm->downMaterialById($material3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $materialsArray = array($material1, $material3);
        $this->assertEquals($materialsArray, array_values($this->repo->find($mm->getId())->getMaterials()->toArray()));
    }

    public function testEmbedLinksInMultimediaObject()
    {
        $link1 = new Link();
        $link2 = new Link();
        $link3 = new Link();

        $this->dm->persist($link1);
        $this->dm->persist($link2);
        $this->dm->persist($link3);

        $mm = new MultimediaObject();
        $mm->addLink($link1);
        $mm->addLink($link2);
        $mm->addLink($link3);

        $this->dm->persist($mm);

        $this->dm->flush();

        $this->assertEquals($link1, $this->repo->find($mm->getId())->getLinkById($link1->getId()));
        $this->assertEquals($link2, $this->repo->find($mm->getId())->getLinkById($link2->getId()));
        $this->assertEquals($link3, $this->repo->find($mm->getId())->getLinkById($link3->getId()));
        $this->assertNull($this->repo->find($mm->getId())->getLinkById(null));

        $mm->removeLinkById($link2->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $linksArray = array($link1, $link3);
        $this->assertEquals(count($linksArray), count($this->repo->find($mm->getId())->getLinks()));
        $this->assertEquals($linksArray, array_values($this->repo->find($mm->getId())->getLinks()->toArray()));

        $mm->upLinkById($link3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $linksArray = array($link3, $link1);
        $this->assertEquals($linksArray, array_values($this->repo->find($mm->getId())->getLinks()->toArray()));

        $mm->downLinkById($link3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $linksArray = array($link1, $link3);
        $this->assertEquals($linksArray, array_values($this->repo->find($mm->getId())->getLinks()->toArray()));
    }

    public function testEmbedTracksInMultimediaObject()
    {
        $track1 = new Track();
        $track2 = new Track();
        $track3 = new Track();

        $this->dm->persist($track1);
        $this->dm->persist($track2);
        $this->dm->persist($track3);

        $mm = new MultimediaObject();
        $mm->addTrack($track1);
        $mm->addTrack($track2);
        $mm->addTrack($track3);

        $this->dm->persist($mm);

        $this->dm->flush();

        $this->assertEquals($track1, $this->repo->find($mm->getId())->getTrackById($track1->getId()));
        $this->assertEquals($track2, $this->repo->find($mm->getId())->getTrackById($track2->getId()));
        $this->assertEquals($track3, $this->repo->find($mm->getId())->getTrackById($track3->getId()));
        $this->assertNull($this->repo->find($mm->getId())->getTrackById(null));

        $mm->removeTrackById($track2->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $tracksArray = array($track1, $track3);
        $this->assertEquals(count($tracksArray), count($this->repo->find($mm->getId())->getTracks()));
        $this->assertEquals($tracksArray, array_values($this->repo->find($mm->getId())->getTracks()->toArray()));

        $mm->upTrackById($track3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $tracksArray = array($track3, $track1);
        $this->assertEquals($tracksArray, array_values($this->repo->find($mm->getId())->getTracks()->toArray()));

        $mm->downTrackById($track3->getId());
        $this->dm->persist($mm);
        $this->dm->flush();

        $tracksArray = array($track1, $track3);
        $this->assertEquals($tracksArray, array_values($this->repo->find($mm->getId())->getTracks()->toArray()));
    }

    public function testFindMultimediaObjectsWithTags()
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

        $mm21->addTag($tag2);

        $mm22->addTag($tag1);

        $mm23->addTag($tag1);

        $mm31->addTag($tag1);

        $mm33->addTag($tag1);

        $mm34->addTag($tag1);

        $mm11->setTitle('mm11');
        $mm12->setTitle('mm12');
        $mm13->setTitle('mm13');
        $mm21->setTitle('mm21');
        $mm22->setTitle('mm22');
        $mm23->setTitle('mm23');
        $mm31->setTitle('mm31');
        $mm32->setTitle('mm32');
        $mm33->setTitle('mm33');
        $mm34->setTitle('mm34');

        $mm11->setPublicDate(new \DateTime('2015-01-03 15:05:16'));
        $mm12->setPublicDate(new \DateTime('2015-01-04 15:05:16'));
        $mm13->setPublicDate(new \DateTime('2015-01-05 15:05:16'));
        $mm21->setPublicDate(new \DateTime('2015-01-06 15:05:16'));
        $mm22->setPublicDate(new \DateTime('2015-01-07 15:05:16'));
        $mm23->setPublicDate(new \DateTime('2015-01-08 15:05:16'));
        $mm31->setPublicDate(new \DateTime('2015-01-09 15:05:16'));
        $mm32->setPublicDate(new \DateTime('2015-01-10 15:05:16'));
        $mm33->setPublicDate(new \DateTime('2015-01-11 15:05:16'));
        $mm34->setPublicDate(new \DateTime('2015-01-12 15:05:16'));

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
        $sortAsc =  array('fieldName' => 'public_date', 'order' => 1);
        $sortDesc = array('fieldName' => 'public_date', 'order' => -1);

        // FIND WITH TAG
        $this->assertEquals(7, count($this->repo->findWithTag($tag1)));
        $limit = 3;
        $this->assertEquals(3, $this->repo->findWithTag($tag1, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(3, $this->repo->findWithTag($tag1, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(3, $this->repo->findWithTag($tag1, $sort, $limit, $page)->count(true));
        $page = 2;
        $this->assertEquals(1, $this->repo->findWithTag($tag1, $sort, $limit, $page)->count(true));
        $page = 3;
        $this->assertEquals(0, $this->repo->findWithTag($tag1, $sort, $limit, $page)->count(true));

        // FIND WITH TAG (SORT)
        $page = 1;
        $arrayAsc = array($mm23, $mm31, $mm33);
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithTag($tag1, $sortAsc, $limit, $page)->toArray()));
        $arrayDesc = array($mm23, $mm22, $mm12);
        $this->assertEquals($arrayDesc, array_values($this->repo->findWithTag($tag1, $sortDesc, $limit, $page)->toArray()));

        $this->assertEquals(2, count($this->repo->findWithTag($tag2)));
        $limit = 1;
        $this->assertEquals(1, $this->repo->findWithTag($tag2, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(1, $this->repo->findWithTag($tag2, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithTag($tag2, $sort, $limit, $page)->count(true));

        // FIND ONE WITH TAG
        $this->assertEquals(1, count($this->repo->findOneWithTag($tag1)));

        // FIND WITH ANY TAG
        $arrayTags = array($tag1, $tag2, $tag3);
        $this->assertEquals(8, $this->repo->findWithAnyTag($arrayTags)->count(true));
        $limit = 3;
        $this->assertEquals(3, $this->repo->findWithAnyTag($arrayTags, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(3, $this->repo->findWithAnyTag($arrayTags, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(3, $this->repo->findWithAnyTag($arrayTags, $sort, $limit, $page)->count(true));
        $page = 2;
        $this->assertEquals(2, $this->repo->findWithAnyTag($arrayTags, $sort, $limit, $page)->count(true));

        // FIND WITH ANY TAG (SORT)
        $arrayAsc = array($mm11, $mm12, $mm21, $mm22, $mm23, $mm31, $mm33, $mm34);
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithAnyTag($arrayTags, $sortAsc)->toArray()));
        $limit = 3;
        $arrayAsc = array($mm11, $mm12, $mm21);
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithAnyTag($arrayTags, $sortAsc, $limit)->toArray()));
        $page = 0;
        $arrayAsc = array($mm11, $mm12, $mm21);
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithAnyTag($arrayTags, $sortAsc, $limit, $page)->toArray()));
        $page = 1;
        $arrayAsc = array($mm22, $mm23, $mm31);
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithAnyTag($arrayTags, $sortAsc, $limit, $page)->toArray()));
        $page = 2;
        $arrayAsc = array($mm33, $mm34);
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithAnyTag($arrayTags, $sortAsc, $limit, $page)->toArray()));
        
        $arrayDesc = array($mm34, $mm33, $mm31, $mm23, $mm22, $mm21, $mm12, $mm11);
        $this->assertEquals($arrayDesc, array_values($this->repo->findWithAnyTag($arrayTags, $sortDesc)->toArray()));
        $limit = 5;
        $page = 0;
        $arrayDesc = array($mm34, $mm33, $mm31, $mm23, $mm22);
        $this->assertEquals($arrayDesc, array_values($this->repo->findWithAnyTag($arrayTags, $sortDesc, $limit, $page)->toArray()));
        $page = 1;
        $arrayDesc = array($mm21, $mm12, $mm11);
        $this->assertEquals($arrayDesc, array_values($this->repo->findWithAnyTag($arrayTags, $sortDesc, $limit, $page)->toArray()));

        // Add more tags
        $mm32->addTag($tag3);
        $this->dm->persist($mm32);
        $this->dm->flush();
        $this->assertEquals(9, $this->repo->findWithAnyTag($arrayTags)->count(true));

        $arrayTags = array($tag2, $tag3);
        $this->assertEquals(3, $this->repo->findWithAnyTag($arrayTags)->count(true));

        // FIND WITH ALL TAGS
        $mm32->addTag($tag2);

        $mm13->addTag($tag1);
        $mm13->addTag($tag2);

        $this->dm->persist($mm13);
        $this->dm->persist($mm32);
        $this->dm->flush();

        $arrayTags = array($tag1, $tag2);
        $this->assertEquals(2, $this->repo->findWithAllTags($arrayTags)->count(true));

        $mm12->addTag($tag2);
        $mm22->addTag($tag2);
        $this->dm->persist($mm12);
        $this->dm->persist($mm22);
        $this->dm->flush();

        $this->assertEquals(4, $this->repo->findWithAllTags($arrayTags)->count(true));
        $limit = 3;
        $this->assertEquals(3, $this->repo->findWithAllTags($arrayTags, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(3, $this->repo->findWithAllTags($arrayTags, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithAllTags($arrayTags, $sort, $limit, $page)->count(true));

        $arrayTags = array($tag2, $tag3);
        $this->assertEquals(1, $this->repo->findWithAllTags($arrayTags)->count(true));

        // FIND WITH ALL TAGS (SORT)
        $arrayTags = array($tag1, $tag2);
        $arrayAsc = array($mm11, $mm12, $mm13, $mm22);
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithAllTags($arrayTags, $sortAsc)->toArray()));
        $arrayDesc = array($mm22, $mm13, $mm12, $mm11);
        $this->assertEquals($arrayDesc, array_values($this->repo->findWithAllTags($arrayTags, $sortDesc)->toArray()));
        $limit = 3;
        $arrayAsc = array($mm11, $mm12, $mm13);
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithAllTags($arrayTags, $sortAsc, $limit)->toArray()));
        $page = 1;
        $arrayAsc = array($mm22);
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithAllTags($arrayTags, $sortAsc, $limit, $page)->toArray()));

        $limit = 2;
        $page = 1;
        $arrayDesc = array($mm12, $mm11);
        $this->assertEquals($arrayDesc, array_values($this->repo->findWithAllTags($arrayTags, $sortDesc, $limit, $page)->toArray()));

        // FIND ONE WITH ALL TAGS
        $arrayTags = array($tag1, $tag2);
        $this->assertEquals(1, count($this->repo->findOneWithAllTags($arrayTags)));

        // FIND WITHOUT TAG
        $this->assertEquals(9, $this->repo->findWithoutTag($tag3)->count(true));
        $limit = 4;
        $this->assertEquals(4, $this->repo->findWithoutTag($tag3, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(4, $this->repo->findWithoutTag($tag3, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(4, $this->repo->findWithoutTag($tag3, $sort, $limit, $page)->count(true));
        $page = 2;
        $this->assertEquals(1, $this->repo->findWithoutTag($tag3, $sort, $limit, $page)->count(true));
        $page = 3;
        $this->assertEquals(0, $this->repo->findWithoutTag($tag3, $sort, $limit, $page)->count(true));

        // FIND WITHOUT TAG (SORT)
        $arrayAsc = array($mm11, $mm12, $mm13, $mm21, $mm22, $mm23, $mm31, $mm33, $mm34);
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithoutTag($tag3, $sortAsc)->toArray()));
        $limit = 6;
        $arrayAsc = array($mm11, $mm12, $mm13, $mm21, $mm22, $mm23);
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithoutTag($tag3, $sortAsc, $limit)->toArray()));
        $page = 1;
        $arrayAsc = array($mm31, $mm33, $mm34);
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithoutTag($tag3, $sortAsc, $limit, $page)->toArray()));

        $arrayDesc = array($mm13, $mm12, $mm11);
        $this->assertEquals($arrayDesc, array_values($this->repo->findWithoutTag($tag3, $sortDesc, $limit, $page)->toArray()));

        // FIND ONE WITHOUT TAG
        $this->assertEquals(1, count($this->repo->findOneWithoutTag($tag2)));

        // FIND WITH ALL TAGS
        // TODO

        // FIND WITHOUT ALL TAGS
        $arrayTags = array($tag2, $tag3);
        $this->assertEquals(4, $this->repo->findWithoutAllTags($arrayTags)->count(true));
        $limit = 3;
        $this->assertEquals(3, $this->repo->findWithoutAllTags($arrayTags, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(3, $this->repo->findWithoutAllTags($arrayTags, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithoutAllTags($arrayTags, $sort, $limit, $page)->count(true));

        $arrayTags = array($tag1, $tag3);
        $this->assertEquals(1, $this->repo->findWithoutAllTags($arrayTags)->count(true));

        $arrayTags = array($tag1, $tag2);
        $this->assertEquals(0, $this->repo->findWithoutAllTags($arrayTags)->count(true));

        // FIND WITHOUT ALL TAGS (SORT)
        $arrayTags = array($tag2, $tag3);
        $arrayAsc = array($mm23, $mm31, $mm33, $mm34);
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithoutAllTags($arrayTags, $sortAsc)->toArray()));
        $limit = 3;
        $page = 1;
        $arrayAsc = array($mm34);
        $this->assertEquals($arrayAsc, array_values($this->repo->findWithoutAllTags($arrayTags, $sortAsc, $limit, $page)->toArray()));

        $page = 0;
        $arrayDesc = array($mm34, $mm33, $mm31);
        $this->assertEquals($arrayDesc, array_values($this->repo->findWithoutAllTags($arrayTags, $sortDesc, $limit, $page)->toArray()));
    }

    public function testFindSeriesFieldWithTags()
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

        // FIND SERIES FIELD WITH TAG
        $this->assertEquals(3, count($this->repo->findSeriesFieldWithTag($tag1)));
        $this->assertEquals(1, count($this->repo->findSeriesFieldWithTag($tag3)));

        // FIND ONE SERIES FIELD WITH TAG
        $this->assertEquals($series3->getId(), $this->repo->findOneSeriesFieldWithTag($tag3));

        // FIND SERIES FIELD WITH ANY TAG
        $arrayTags = array($tag1, $tag2);
        $this->assertEquals(3, $this->repo->findSeriesFieldWithAnyTag($arrayTags)->count(true));

        $arrayTags = array($tag3);
        $this->assertEquals(1, $this->repo->findSeriesFieldWithAnyTag($arrayTags)->count(true));

        // FIND SERIES FIELD WITH ALL TAGS
        $arrayTags = array($tag1, $tag2);
        $this->assertEquals(2, $this->repo->findSeriesFieldWithAllTags($arrayTags)->count(true));

        $arrayTags = array($tag2, $tag3);
        $this->assertEquals(1, $this->repo->findSeriesFieldWithAllTags($arrayTags)->count(true));

        // FIND ONE SERIES FIELD WITH ALL TAGS
        $arrayTags = array($tag1, $tag2);
        $this->assertEquals(1, count($this->repo->findOneSeriesFieldWithAllTags($arrayTags)));

        $arrayTags = array($tag2, $tag3);
        $this->assertEquals(1, count($this->repo->findOneSeriesFieldWithAllTags($arrayTags)));
        $this->assertEquals($series3->getId(), $this->repo->findOneSeriesFieldWithAllTags($arrayTags));
    }

    public function testFindDistinctPics()
    {
        $pic1 = new Pic();
        $url1 = 'http://domain.com/pic1.png';
        $pic1->setUrl($url1);

        $pic2 = new Pic();
        $url2 = 'http://domain.com/pic2.png';
        $pic2->setUrl($url2);

        $pic3 = new Pic();
        $url3 = 'http://domain.com/pic3.png';
        $pic3->setUrl($url3);

        $pic4 = new Pic();
        $pic4->setUrl($url3);

        $pic5 = new Pic();
        $url5 = 'http://domain.com/pic5.png';
        $pic5->setUrl($url5);

        $this->dm->persist($pic1);
        $this->dm->persist($pic2);
        $this->dm->persist($pic3);
        $this->dm->persist($pic4);
        $this->dm->persist($pic5);
        $this->dm->flush();

        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI);

        $series1 = $this->createSeries('Series 1');
        $series2 = $this->createSeries('Series 2');

        $series1 = $this->dm->find('PumukitSchemaBundle:Series', $series1->getId());
        $series2 = $this->dm->find('PumukitSchemaBundle:Series', $series2->getId());

        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);

        $mm21 = $this->factoryService->createMultimediaObject($series2);

        $mm11 = $this->mmsPicService->addPicUrl($mm11, $pic1);
        $mm11 = $this->mmsPicService->addPicUrl($mm11, $pic2);
        $mm11 = $this->mmsPicService->addPicUrl($mm11, $pic4);

        $mm12 = $this->mmsPicService->addPicUrl($mm12, $pic3);

        $mm21 = $this->mmsPicService->addPicUrl($mm21, $pic5);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm21);
        $this->dm->flush();

        $this->assertEquals(3, count($mm11->getPics()));
        $this->assertEquals(3, count($this->repo->find($mm11->getId())->getPics()));
        $this->assertEquals(1, count($mm12->getPics()));
        $this->assertEquals(1, count($this->repo->find($mm12->getId())->getPics()));
        $this->assertEquals(1, count($mm21->getPics()));
        $this->assertEquals(1, count($this->repo->find($mm21->getId())->getPics()));

        $this->assertEquals(3, count($this->repo->findDistinctUrlPicsInSeries($series1)));

        $this->assertEquals(4, count($this->repo->findDistinctUrlPics()));

        $mm11->setPublicDate(new \DateTime('now'));
        $mm12->setPublicDate(new \DateTime('now'));
        $mm21->setPublicDate(new \DateTime('now'));

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm21);
        $this->dm->flush();

        /* TODO Check sort
        $arrayPics = array($pic1->getUrl(), $pic2->getUrl(), $pic4->getUrl(), $pic5->getUrl());
        $this->assertEquals($arrayPics, $this->repo->findDistinctUrlPics()->toArray());
        */     
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

    private function createMultimediaObjectAssignedToSeries($title, Series $series)
    {
        $rank = 1;
        $status = MultimediaObject::STATUS_NEW;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $subtitle = 'Subtitle';
        $description = "Description";
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
        $test_date = new \DateTime("now");

        $series = $this->factoryService->createSeries();

        $series->setTitle($title);
        $series->setSubtitle($subtitle);
        $series->setDescription($description);
        $series->setPublicDate($test_date);

        $this->dm->persist($series);
        $this->dm->flush();

        return $series;
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
