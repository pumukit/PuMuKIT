<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\User;

/**
 * @internal
 * @coversNothing
 */
class PersonServiceTest extends PumukitTestCase
{
    private $repo;
    private $repoMmobj;
    private $personService;
    private $factoryService;
    private $roleRepo;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(Person::class);
        $this->roleRepo = $this->dm->getRepository(Role::class);
        $this->repoMmobj = $this->dm->getRepository(MultimediaObject::class);
        $this->personService = static::$kernel->getContainer()->get('pumukitschema.person');
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        $this->roleRepo = null;
        $this->repoMmobj = null;
        $this->personService = null;
        $this->factoryService = null;
        gc_collect_cycles();
    }

    public function testSavePerson()
    {
        $person = new Person();

        $name = 'John Smith';
        $person->setName($name);

        $person = $this->personService->savePerson($person);

        static::assertNotNull($person->getId());
    }

    public function testSaveRole()
    {
        $role = new Role();

        $code = 'Actor';
        $role->setCod($code);

        $role = $this->personService->saveRole($role);

        static::assertNotNull($role->getId());
    }

    public function testFindPersonById()
    {
        $person = new Person();

        $name = 'John Smith';
        $person->setName($name);

        $person = $this->personService->savePerson($person);

        static::assertEquals($person, $this->personService->findPersonById($person->getId()));
    }

    public function testFindRoleById()
    {
        $role = new Role();

        $code = 'actor';
        $role->setCod($code);

        $role = $this->personService->saveRole($role);

        static::assertEquals($role, $this->personService->findRoleById($role->getId()));
    }

    public function testFindPersonByEmail()
    {
        $person = new Person();

        $name = 'John Smith';
        $email = 'john.smith@mail.com';
        $person->setName($name);
        $person->setEmail($email);

        $person = $this->personService->savePerson($person);

        static::assertEquals($person, $this->personService->findPersonByEmail($email));
    }

    public function testUpdatePersonAndUpdateRole()
    {
        $personJohn = new Person();
        $nameJohn = 'John Smith';
        $personJohn->setName($nameJohn);

        $personBob = new Person();
        $nameBob = 'Bob Clark';
        $personBob->setName($nameBob);

        $personJohn = $this->personService->savePerson($personJohn);
        $personBob = $this->personService->savePerson($personBob);

        $roleActor = new Role();
        $codActor = 'actor';
        $roleActor->setCod($codActor);

        $rolePresenter = new Role();
        $codPresenter = 'presenter';
        $rolePresenter->setCod($codPresenter);

        $this->dm->persist($roleActor);
        $this->dm->persist($rolePresenter);
        $this->dm->flush();

        $series = $this->factoryService->createSeries();

        $mm1 = $this->factoryService->createMultimediaObject($series);
        $title1 = 'Multimedia Object 1';
        $mm1->setTitle($title1);
        $mm1->addPersonWithRole($personJohn, $roleActor);
        $mm1->addPersonWithRole($personBob, $roleActor);
        $mm1->addPersonWithRole($personJohn, $rolePresenter);

        $mm2 = $this->factoryService->createMultimediaObject($series);
        $title2 = 'Multimedia Object 2';
        $mm2->setTitle($title2);
        $mm2->addPersonWithRole($personJohn, $roleActor);
        $mm2->addPersonWithRole($personBob, $rolePresenter);
        $mm2->addPersonWithRole($personJohn, $rolePresenter);

        $mm3 = $this->factoryService->createMultimediaObject($series);
        $title3 = 'Multimedia Object 3';
        $mm3->setTitle($title3);
        $mm3->addPersonWithRole($personJohn, $roleActor);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->flush();

        static::assertEquals($this->personService->findPersonById($personJohn->getId())->getEmail(), '');
        static::assertEquals($this->personService->findPersonById($personBob->getId())->getEmail(), '');
        static::assertEquals($mm1->getPersonWithRole($personJohn, $roleActor)->getEmail(), '');
        static::assertEquals($mm1->getPersonWithRole($personJohn, $rolePresenter)->getEmail(), '');
        static::assertEquals($mm1->getPersonWithRole($personBob, $roleActor)->getEmail(), '');
        static::assertEquals($mm2->getPersonWithRole($personJohn, $roleActor)->getEmail(), '');
        static::assertEquals($mm2->getPersonWithRole($personBob, $rolePresenter)->getEmail(), '');
        static::assertEquals($mm2->getPersonWithRole($personJohn, $rolePresenter)->getEmail(), '');
        static::assertEquals($mm3->getPersonWithRole($personJohn, $roleActor)->getEmail(), '');

        $emailJohn = 'johnsmith@mail.com';
        $personJohn->setEmail($emailJohn);

        $personJohn = $this->personService->updatePerson($personJohn);

        static::assertEquals($emailJohn, $this->personService->findPersonById($personJohn->getId())->getEmail());
        static::assertEquals($this->personService->findPersonById($personBob->getId())->getEmail(), '');
        static::assertEquals($emailJohn, $mm1->getPersonWithRole($personJohn, $roleActor)->getEmail());
        static::assertEquals($emailJohn, $mm1->getPersonWithRole($personJohn, $rolePresenter)->getEmail());
        static::assertEquals($mm1->getPersonWithRole($personBob, $roleActor)->getEmail(), '');
        static::assertEquals($emailJohn, $mm2->getPersonWithRole($personJohn, $roleActor)->getEmail());
        static::assertEquals($mm2->getPersonWithRole($personBob, $rolePresenter)->getEmail(), '');
        static::assertEquals($emailJohn, $mm2->getPersonWithRole($personJohn, $rolePresenter)->getEmail());
        static::assertEquals($emailJohn, $mm3->getPersonWithRole($personJohn, $roleActor)->getEmail());

        // Test update embedded person
        $emailBob = 'bobclark@mail.com';
        $personBob->setEmail($emailBob);

        $personBob = $this->personService->updatePerson($personBob);

        static::assertEquals($emailJohn, $this->personService->findPersonById($personJohn->getId())->getEmail());
        static::assertEquals($emailBob, $this->personService->findPersonById($personBob->getId())->getEmail());
        static::assertEquals($emailJohn, $mm1->getPersonWithRole($personJohn, $roleActor)->getEmail());
        static::assertEquals($emailJohn, $mm1->getPersonWithRole($personJohn, $rolePresenter)->getEmail());
        static::assertEquals($emailBob, $mm1->getPersonWithRole($personBob, $roleActor)->getEmail());
        static::assertEquals($emailJohn, $mm2->getPersonWithRole($personJohn, $roleActor)->getEmail());
        static::assertEquals($emailBob, $mm2->getPersonWithRole($personBob, $rolePresenter)->getEmail());
        static::assertEquals($emailJohn, $mm2->getPersonWithRole($personJohn, $rolePresenter)->getEmail());
        static::assertEquals($emailJohn, $mm3->getPersonWithRole($personJohn, $roleActor)->getEmail());

        // Test update embedded role
        $newActorCode = 'NewActor';
        $roleActor->setCod($newActorCode);

        $roleActor = $this->personService->updateRole($roleActor);

        $this->dm->refresh($mm1);
        $this->dm->refresh($mm2);
        $this->dm->refresh($mm3);

        static::assertEquals($newActorCode, $this->roleRepo->find($roleActor->getId())->getCod());
        static::assertEquals($newActorCode, $mm1->getEmbeddedRole($roleActor)->getCod());
        static::assertEquals($newActorCode, $mm2->getEmbeddedRole($roleActor)->getCod());
        static::assertEquals($newActorCode, $mm3->getEmbeddedRole($roleActor)->getCod());

        $newPresenterCode = 'NewPresenter';
        $rolePresenter->setCod($newPresenterCode);

        $rolePresenter = $this->personService->updateRole($rolePresenter);

        $this->dm->refresh($mm1);
        $this->dm->refresh($mm2);
        $this->dm->refresh($mm3);

        static::assertEquals($newPresenterCode, $this->roleRepo->find($rolePresenter->getId())->getCod());
        static::assertEquals($newPresenterCode, $mm1->getEmbeddedRole($rolePresenter)->getCod());
        static::assertEquals($newPresenterCode, $mm2->getEmbeddedRole($rolePresenter)->getCod());
        static::assertNull($mm3->getEmbeddedRole($rolePresenter));
    }

    public function testFindSeriesWithPerson()
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
        $title32 = 'Multimedia Object 32';
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

        $seriesJohn = $this->personService->findSeriesWithPerson($personJohn);
        $seriesBob = $this->personService->findSeriesWithPerson($personBob);
        $seriesKate = $this->personService->findSeriesWithPerson($personKate);

        static::assertCount(2, $seriesJohn);
        static::assertCount(2, $seriesBob);
        static::assertCount(2, $seriesKate);

        static::assertContains($series1, $seriesJohn->toArray());
        static::assertContains($series3, $seriesJohn->toArray());
        static::assertContains($series1, $seriesBob->toArray());
        static::assertContains($series3, $seriesBob->toArray());
        static::assertContains($series1, $seriesKate->toArray());
        static::assertContains($series2, $seriesKate->toArray());

        $seriesKate1 = $this->personService->findSeriesWithPerson($personKate, 1);
        static::assertEquals([$series1], $seriesKate1->toArray());
    }

    public function testCreateRelationPerson()
    {
        $roleActor = new Role();
        $codActor = 'actor';
        $roleActor->setCod($codActor);

        $this->dm->persist($roleActor);
        $this->dm->flush();

        $series = $this->factoryService->createSeries();

        $mm = $this->factoryService->createMultimediaObject($series);
        $title = 'Multimedia Object';
        $mm->setTitle($title);

        $personJohn = new Person();
        $nameJohn = 'John Smith';
        $personJohn->setName($nameJohn);

        static::assertCount(0, $mm->getPeopleByRole($roleActor));

        $mm = $this->personService->createRelationPerson($personJohn, $roleActor, $mm);

        static::assertCount(1, $mm->getPeopleByRole($roleActor));
    }

    public function testAutoCompletePeopleByName()
    {
        static::assertCount(0, $this->personService->autoCompletePeopleByName('john'));

        $personJohn = new Person();
        $nameJohn = 'John Smith';
        $personJohn->setName($nameJohn);

        $personBob = new Person();
        $nameBob = 'Bob Clark';
        $personBob->setName($nameBob);

        $personKate = new Person();
        $nameKate = 'Kate Simmons';
        $personKate->setName($nameKate);

        $personBobby = new Person();
        $nameBobby = 'Bobby Weissmann';
        $personBobby->setName($nameBobby);

        $this->dm->persist($personJohn);
        $this->dm->persist($personBob);
        $this->dm->persist($personKate);
        $this->dm->persist($personBobby);
        $this->dm->flush();

        $peopleJohn = array_values($this->personService->autoCompletePeopleByName('john')->toArray());
        static::assertCount(1, $peopleJohn);
        static::assertEquals($personJohn, $peopleJohn[0]);

        $peopleBob = array_values($this->personService->autoCompletePeopleByName('bob')->toArray());
        static::assertCount(2, $peopleBob);
        static::assertEquals([$personBob, $personBobby], $peopleBob);

        $peopleKat = array_values($this->personService->autoCompletePeopleByName('kat')->toArray());
        static::assertCount(1, $peopleKat);
        static::assertEquals($personKate, $peopleKat[0]);

        $peopleSm = array_values($this->personService->autoCompletePeopleByName('sm')->toArray());
        static::assertCount(2, $peopleSm);
        static::assertEquals([$personJohn, $personBobby], $peopleSm);
    }

    public function testDeleteRelation()
    {
        $personBob = new Person();
        $nameBob = 'Bob Clark';
        $personBob->setName($nameBob);

        $personBob = $this->personService->savePerson($personBob);

        $roleActor = new Role();
        $codActor = 'actor';
        $roleActor->setCod($codActor);

        $this->dm->persist($roleActor);
        $this->dm->flush();

        $series = $this->factoryService->createSeries();

        $mm1 = $this->factoryService->createMultimediaObject($series);
        $title1 = 'Multimedia Object 1';
        $mm1->setTitle($title1);
        $mm1->addPersonWithRole($personBob, $roleActor);

        $this->dm->persist($mm1);
        $this->dm->flush();

        $personBobId = $personBob->getId();

        static::assertCount(1, $this->repoMmobj->findByPersonId($personBobId));
        static::assertEquals($personBob, $this->repo->find($personBobId));

        $this->personService->deleteRelation($personBob, $roleActor, $mm1);

        static::assertCount(0, $this->repoMmobj->findByPersonId($personBobId));
    }

    public function testBatchDeletePerson()
    {
        $personJohn = new Person();
        $nameJohn = 'John Smith';
        $personJohn->setName($nameJohn);

        $personBob = new Person();
        $nameBob = 'Bob Clark';
        $personBob->setName($nameBob);

        $personJohn = $this->personService->savePerson($personJohn);
        $personBob = $this->personService->savePerson($personBob);

        $roleActor = new Role();
        $codActor = 'actor';
        $roleActor->setCod($codActor);

        $rolePresenter = new Role();
        $codPresenter = 'presenter';
        $rolePresenter->setCod($codPresenter);

        $this->dm->persist($roleActor);
        $this->dm->persist($rolePresenter);
        $this->dm->flush();

        $series = $this->factoryService->createSeries();

        $mm1 = $this->factoryService->createMultimediaObject($series);
        $title1 = 'Multimedia Object 1';
        $mm1->setTitle($title1);
        $mm1->addPersonWithRole($personJohn, $roleActor);
        $mm1->addPersonWithRole($personBob, $roleActor);
        $mm1->addPersonWithRole($personJohn, $rolePresenter);

        $mm2 = $this->factoryService->createMultimediaObject($series);
        $title2 = 'Multimedia Object 2';
        $mm2->setTitle($title2);
        $mm2->addPersonWithRole($personJohn, $roleActor);
        $mm2->addPersonWithRole($personBob, $rolePresenter);
        $mm2->addPersonWithRole($personBob, $roleActor);

        $mm3 = $this->factoryService->createMultimediaObject($series);
        $title3 = 'Multimedia Object 3';
        $mm3->setTitle($title3);
        $mm3->addPersonWithRole($personJohn, $roleActor);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->flush();

        $personBobId = $personBob->getId();
        $personJohnId = $personJohn->getId();

        static::assertCount(2, $this->repoMmobj->findByPersonId($personBobId));
        static::assertCount(3, $this->repoMmobj->findByPersonId($personJohnId));
        static::assertEquals($personBob, $this->repo->find($personBobId));
        static::assertEquals($personJohn, $this->repo->find($personJohnId));

        $this->personService->batchDeletePerson($personBob);

        static::assertCount(0, $this->repoMmobj->findByPersonId($personBobId));
        static::assertCount(3, $this->repoMmobj->findByPersonId($personJohnId));
        static::assertNull($this->repo->find($personBobId));
        static::assertEquals($personJohn, $this->repo->find($personJohnId));

        $this->personService->batchDeletePerson($personJohn);

        static::assertCount(0, $this->repoMmobj->findByPersonId($personBobId));
        static::assertCount(0, $this->repoMmobj->findByPersonId($personJohnId));
        static::assertNull($this->repo->find($personBobId));
        static::assertNull($this->repo->find($personJohnId));
    }

    public function testCountMultimediaObjectsWithPerson()
    {
        $personJohn = new Person();
        $nameJohn = 'John Smith';
        $personJohn->setName($nameJohn);

        $roleActor = new Role();
        $codActor = 'actor';
        $roleActor->setCod($codActor);

        $this->dm->persist($roleActor);
        $this->dm->flush();

        $personJohn = $this->personService->savePerson($personJohn);

        $series = $this->factoryService->createSeries();
        $mm1 = $this->factoryService->createMultimediaObject($series);

        $mm1->addPersonWithRole($personJohn, $roleActor);

        $this->dm->persist($mm1);
        $this->dm->flush();

        static::assertEquals(1, $this->personService->countMultimediaObjectsWithPerson($personJohn));
    }

    public function testUpAndDownPersonWithRole()
    {
        $personJohn = new Person();
        $nameJohn = 'John Smith';
        $personJohn->setName($nameJohn);

        $personBob = new Person();
        $nameBob = 'Bob Clark';
        $personBob->setName($nameBob);

        $personJohn = $this->personService->savePerson($personJohn);
        $personBob = $this->personService->savePerson($personBob);

        $roleActor = new Role();
        $codActor = 'actor';
        $roleActor->setCod($codActor);

        $rolePresenter = new Role();
        $codPresenter = 'presenter';
        $rolePresenter->setCod($codPresenter);

        $this->dm->persist($roleActor);
        $this->dm->persist($rolePresenter);
        $this->dm->flush();

        $series = $this->factoryService->createSeries();

        $mm1 = $this->factoryService->createMultimediaObject($series);
        $title1 = 'Multimedia Object 1';
        $mm1->setTitle($title1);
        $mm1->addPersonWithRole($personJohn, $roleActor);
        $mm1->addPersonWithRole($personBob, $roleActor);
        $mm1->addPersonWithRole($personJohn, $rolePresenter);

        $mm2 = $this->factoryService->createMultimediaObject($series);
        $title2 = 'Multimedia Object 2';
        $mm2->setTitle($title2);
        $mm2->addPersonWithRole($personJohn, $roleActor);
        $mm2->addPersonWithRole($personBob, $rolePresenter);
        $mm2->addPersonWithRole($personBob, $roleActor);

        $mm3 = $this->factoryService->createMultimediaObject($series);
        $title3 = 'Multimedia Object 3';
        $mm3->setTitle($title3);
        $mm3->addPersonWithRole($personJohn, $roleActor);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->flush();

        $mm1PeopleActor = $mm1->getPeopleByRole($roleActor);
        static::assertEquals($personJohn->getId(), $mm1PeopleActor[0]->getId());
        static::assertEquals($personBob->getId(), $mm1PeopleActor[1]->getId());

        $this->personService->upPersonWithRole($personBob, $roleActor, $mm1);

        $mm1PeopleActor = $mm1->getPeopleByRole($roleActor);
        static::assertEquals($personBob->getId(), $mm1PeopleActor[0]->getId());
        static::assertEquals($personJohn->getId(), $mm1PeopleActor[1]->getId());

        $personKate = new Person();
        $nameKate = 'Kate Simmons';
        $personKate->setName($nameKate);
        $personKate = $this->personService->savePerson($personKate);

        $mm1->addPersonWithRole($personKate, $roleActor);
        $this->dm->persist($mm1);
        $this->dm->flush();

        $mm1PeopleActor = $mm1->getPeopleByRole($roleActor);
        static::assertEquals($personBob->getId(), $mm1PeopleActor[0]->getId());
        static::assertEquals($personJohn->getId(), $mm1PeopleActor[1]->getId());
        static::assertEquals($personKate->getId(), $mm1PeopleActor[2]->getId());

        $this->personService->downPersonWithRole($personBob, $roleActor, $mm1);

        $mm1PeopleActor = $mm1->getPeopleByRole($roleActor);
        static::assertEquals($personJohn->getId(), $mm1PeopleActor[0]->getId());
        static::assertEquals($personBob->getId(), $mm1PeopleActor[1]->getId());
        static::assertEquals($personKate->getId(), $mm1PeopleActor[2]->getId());

        $this->personService->downPersonWithRole($personBob, $roleActor, $mm1);

        $mm1PeopleActor = $mm1->getPeopleByRole($roleActor);
        static::assertEquals($personJohn->getId(), $mm1PeopleActor[0]->getId());
        static::assertEquals($personKate->getId(), $mm1PeopleActor[1]->getId());
        static::assertEquals($personBob->getId(), $mm1PeopleActor[2]->getId());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage remove Person with id
     */
    public function testDeletePerson()
    {
        static::assertCount(0, $this->repo->findAll());

        $person = new Person();
        $person->setName('Person');
        $this->dm->persist($person);
        $this->dm->flush();

        static::assertCount(1, $this->repo->findAll());

        $this->personService->deletePerson($person);

        static::assertCount(0, $this->repo->findAll());

        $personBob = new Person();
        $personBob->setName('Bob');

        $roleActor = new Role();
        $codActor = 'actor';
        $roleActor->setCod($codActor);

        $this->dm->persist($personBob);
        $this->dm->persist($roleActor);
        $this->dm->flush();

        $series = $this->factoryService->createSeries();

        $mm = $this->factoryService->createMultimediaObject($series);
        $mm->setTitle('Multimedia Object');
        $mm->addPersonWithRole($personBob, $roleActor);

        $this->dm->persist($mm);
        $this->dm->persist($series);
        $this->dm->flush();

        static::assertCount(1, $this->repo->findAll());

        $this->personService->deletePerson($personBob);

        static::assertCount(1, $this->repo->findAll());
    }

    public function testReferencePersonIntoUser()
    {
        static::assertCount(0, $this->repo->findAll());

        $username = 'user1';
        $fullname = 'User fullname';
        $email = 'user@mail.com';

        $user = new User();
        $user->setUsername($username);
        $user->setFullname($fullname);
        $user->setEmail($email);

        $this->dm->persist($user);
        $this->dm->flush();

        $user = $this->personService->referencePersonIntoUser($user);

        $people = $this->repo->findAll();
        static::assertCount(1, $people);

        $person = $people[0];

        static::assertEquals($person, $user->getPerson());
        static::assertEquals($user, $person->getUser());

        static::assertEquals($fullname, $user->getFullname());
        static::assertEquals($fullname, $person->getName());

        static::assertEquals($email, $user->getEmail());
        static::assertEquals($email, $person->getEmail());

        $user = $this->personService->referencePersonIntoUser($user);
        $people = $this->repo->findAll();
        static::assertCount(1, $people);

        $username2 = 'user2';
        $fullname2 = 'User fullname 2';
        $email2 = 'user2@mail.com';

        $user2 = new User();
        $user2->setUsername($username2);
        $user2->setFullname($fullname2);
        $user2->setEmail($email2);

        $this->dm->persist($user2);
        $this->dm->flush();

        $user2 = $this->personService->referencePersonIntoUser($user2);

        $people = $this->repo->findAll();
        static::assertCount(2, $people);

        $person = $people[1];

        static::assertEquals($person, $user2->getPerson());
        static::assertEquals($user2, $person->getUser());
    }

    public function testGetRoles()
    {
        $role1 = new Role();
        $role1->setCod('role1');

        $role2 = new Role();
        $role2->setCod('role2');

        $role3 = new Role();
        $role3->setCod('role3');

        $this->dm->persist($role1);
        $this->dm->persist($role2);
        $this->dm->persist($role3);
        $this->dm->flush();

        static::assertCount(3, $this->personService->getRoles());
    }

    public function testRemoveUserFromPerson()
    {
        $user = new User();
        $user->setUsername('test');
        $this->dm->persist($user);
        $this->dm->flush();

        $person = new Person();
        $person->setName('test');
        $this->dm->persist($person);
        $this->dm->flush();

        $user->setPerson($person);
        $person->setUser($user);

        $this->dm->persist($person);
        $this->dm->persist($user);
        $this->dm->flush();

        static::assertEquals($person, $user->getPerson());
        static::assertEquals($user, $person->getUser());

        $this->personService->removeUserFromPerson($user, $person, true);

        static::assertEquals($person, $user->getPerson());
        static::assertEquals(null, $person->getUser());
    }
}
