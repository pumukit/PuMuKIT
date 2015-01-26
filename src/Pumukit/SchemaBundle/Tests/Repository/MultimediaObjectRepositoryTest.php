<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\Link;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\Broadcast;
use Doctrine\ODM\MongoDB\Cursor;
use Doctrine\Common\Collections\ArrayCollection;

class MultimediaObjectRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $qb;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();
        $this->dm = $kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository('PumukitSchemaBundle:MultimediaObject');
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
        $series_type = $this->createSeriesType("Medieval Fantasy Sitcom");

        $series_main = $this->createSeries("Stark's growing pains");
        $series_wall = $this->createSeries("The Wall");
        $series_lhazar = $this->createSeries("A quiet life");

        $person_ned = $this->createPerson('Ned');
        $person_benjen = $this->createPerson('Benjen');

        $role_lord = $this->createRole("Lord");
        $role_ranger = $this->createRole("Ranger");
        $role_hand = $this->createRole("Hand");

        $series_type->addSeries($series_main);

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
        
        // TODO - FAILS
        //$this->assertEquals(0, count($mmobj_benjen_lord));
        //$this->assertEquals(0, count($mmobj_ned_ranger));
        $this->assertEquals(0, count($mmobj_benjen_hand));

        // Test find by series
        $mmobj_series_main = $this->getMultimediaObjectsWithSeries($series_main);
        $mmobj_series_wall = $this->getMultimediaObjectsWithSeries($series_wall);
        $mmobj_series_lhazar = $this->getMultimediaObjectsWithSeries($series_lhazar);

        $this->assertEquals(2, count($mmobj_series_main));
        $this->assertEquals(1, count($mmobj_series_wall));
        $this->assertEquals(1, count($mmobj_series_lhazar));

        $seriesBenjen = $this->repo->findSeriesFieldByPerson($person_benjen);
        $seriesNed = $this->repo->findSeriesFieldByPerson($person_ned);
        $this->assertEquals(2, count($seriesBenjen));

        /*
        $this->assertEquals($series_main->getId(), $seriesBenjen[0]->getId());
        $this->assertEquals($series_wall->getId(), $seriesBenjen[1]->getId());

        $this->assertEquals(2, count($seriesNed));
        $this->assertEquals($series_main->getId(), $seriesNed->toArray()[0]->getId());
        $this->assertEquals($series_lhazar->getId(), $seriesNed->toArray()[1]->getId());
        */
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
      //$this->assertEquals(4,count($this->repo->findBySeries($series_main)));
    }

    public function testFindWithStatus()
    {
        $series = $this->createSeries('Serie prueba status');

        $mmPrototype = $this->createMultimediaObjectAssignedToSeries('Status prototype', $series);
        $mmPrototype->setStatus(MultimediaObject::STATUS_PROTOTYPE);

        $mmNew = $this->createMultimediaObjectAssignedToSeries('Status new', $series);
        $mmNew->setStatus(MultimediaObject::STATUS_NEW);

        $mmHide = $this->createMultimediaObjectAssignedToSeries('Status hide', $series);
        $mmHide->setStatus(MultimediaObject::STATUS_HIDE);

        $mmBloq = $this->createMultimediaObjectAssignedToSeries('Status bloq', $series);
        $mmBloq->setStatus(MultimediaObject::STATUS_BLOQ);

        $mmNormal = $this->createMultimediaObjectAssignedToSeries('Status normal', $series);
        $mmNormal->setStatus(MultimediaObject::STATUS_NORMAL);

        $this->dm->persist($mmPrototype);
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

        $mmArray = array($mmPrototype->getId() => $mmPrototype);
        $this->assertEquals($mmArray, $this->repo->findWithStatus($series, array(MultimediaObject::STATUS_PROTOTYPE))->toArray());
        $mmArray = array($mmNew->getId() => $mmNew);
        $this->assertEquals($mmArray, $this->repo->findWithStatus($series, array(MultimediaObject::STATUS_NEW))->toArray());
        $mmArray = array($mmHide->getId() => $mmHide);
        $this->assertEquals($mmArray, $this->repo->findWithStatus($series, array(MultimediaObject::STATUS_HIDE))->toArray());
        $mmArray = array($mmBloq->getId() => $mmBloq);
        $this->assertEquals($mmArray, $this->repo->findWithStatus($series, array(MultimediaObject::STATUS_BLOQ))->toArray());
        $mmArray = array($mmNormal->getId() => $mmNormal);
        $this->assertEquals($mmArray, $this->repo->findWithStatus($series, array(MultimediaObject::STATUS_NORMAL))->toArray());
        $mmArray = array($mmPrototype->getId() => $mmPrototype, $mmNew->getId() => $mmNew);
        $this->assertEquals($mmArray, $this->repo->findWithStatus($series, array(MultimediaObject::STATUS_PROTOTYPE, MultimediaObject::STATUS_NEW))->toArray());
        $mmArray = array($mmNormal->getId() => $mmNormal, $mmNew->getId() => $mmNew, $mmHide->getId() => $mmHide);
        $this->assertEquals($mmArray, $this->repo->findWithStatus($series, array(MultimediaObject::STATUS_NORMAL, MultimediaObject::STATUS_NEW, MultimediaObject::STATUS_HIDE))->toArray());
    }

    public function testFindPrototype()
    {
        $series = $this->createSeries('Serie prueba status');

        $mmPrototype = $this->createMultimediaObjectAssignedToSeries('Status prototype', $series);
        $mmPrototype->setStatus(MultimediaObject::STATUS_PROTOTYPE);

        $mmNew = $this->createMultimediaObjectAssignedToSeries('Status new', $series);
        $mmNew->setStatus(MultimediaObject::STATUS_NEW);

        $mmHide = $this->createMultimediaObjectAssignedToSeries('Status hide', $series);
        $mmHide->setStatus(MultimediaObject::STATUS_HIDE);

        $mmBloq = $this->createMultimediaObjectAssignedToSeries('Status bloq', $series);
        $mmBloq->setStatus(MultimediaObject::STATUS_BLOQ);

        $mmNormal = $this->createMultimediaObjectAssignedToSeries('Status normal', $series);
        $mmNormal->setStatus(MultimediaObject::STATUS_NORMAL);

        $this->dm->persist($mmPrototype);
        $this->dm->persist($mmNew);
        $this->dm->persist($mmHide);
        $this->dm->persist($mmBloq);
        $this->dm->persist($mmNormal);
        $this->dm->flush();

        $this->assertEquals(1, count($this->repo->findPrototype($series)));
        $this->assertEquals($mmPrototype, $this->repo->findPrototype($series));
    }

    public function testFindWithoutPrototype()
    {
        $series = $this->createSeries('Serie prueba status');

        $mmPrototype = $this->createMultimediaObjectAssignedToSeries('Status prototype', $series);
        $mmPrototype->setStatus(MultimediaObject::STATUS_PROTOTYPE);

        $mmNew = $this->createMultimediaObjectAssignedToSeries('Status new', $series);
        $mmNew->setStatus(MultimediaObject::STATUS_NEW);

        $mmHide = $this->createMultimediaObjectAssignedToSeries('Status hide', $series);
        $mmHide->setStatus(MultimediaObject::STATUS_HIDE);

        $mmBloq = $this->createMultimediaObjectAssignedToSeries('Status bloq', $series);
        $mmBloq->setStatus(MultimediaObject::STATUS_BLOQ);

        $mmNormal = $this->createMultimediaObjectAssignedToSeries('Status normal', $series);
        $mmNormal->setStatus(MultimediaObject::STATUS_NORMAL);

        $this->dm->persist($mmPrototype);
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
        $status = MultimediaObject::STATUS_NORMAL;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $subtitle = 'Subtitle';
        $description = "Description";
        $duration = 123;

        $mm = new MultimediaObject();

        $mm->setStatus($status);
        $mm->setRecordDate($record_date);
        $mm->setPublicDate($public_date);
        $mm->setTitle($title);
        $mm->setSubtitle($subtitle);
        $mm->setDescription($description);
        $mm->setDuration($duration);

        $mm->setSeries($series);

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

        $serie = new Series();

        $serie->setTitle($title);
        $serie->setSubtitle($subtitle);
        $serie->setDescription($description);
        $serie->setPublicDate($test_date);

        $this->dm->persist($serie);
        $this->dm->flush();

        return $serie;
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

    private function getMultimediaObjectsWithSeries(Series $series)
    {
        $series_object = $this->dm
                ->find('PumukitSchemaBundle:Series', $series->getId());
        $mmobj_series = $this->dm
                    ->createQueryBuilder('PumukitSchemaBundle:MultimediaObject')
                    ->field('series')->references($series_object)
                    ->getQuery()->execute();

        return $mmobj_series;
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
