<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class PersonServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $personService;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
          ->getRepository('PumukitSchemaBundle:Person');
        $this->personService = $kernel->getContainer()
          ->get('pumukitschema.person');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Person')->remove(array());
        $this->dm->flush();
    }

    public function testSavePerson()
    {
        $person = new Person();
        
        $name = 'John Smith';
        $person->setName($name);

        $person = $this->personService->savePerson($person);

        $this->assertNotNull($person->getId());
    }

    public function testFindPersonById()
    {
        $person = new Person();

        $name = 'John Smith';
        $person->setName($name);
        
        $person = $this->personService->savePerson($person);

        $this->assertEquals($person, $this->personService->findPersonById($person->getId()));
    }

    public function testUpdatePerson()
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

        $mm1 = new MultimediaObject();
        $title1 = 'Multimedia Object 1';
        $mm1->setTitle($title1);
        $mm1->addPersonWithRole($personJohn, $roleActor);
        $mm1->addPersonWithRole($personBob, $roleActor);
        $mm1->addPersonWithRole($personJohn, $rolePresenter);

        $mm2 = new MultimediaObject();
        $title2 = 'Multimedia Object 2';
        $mm2->setTitle($title2);
        $mm2->addPersonWithRole($personJohn, $roleActor);
        $mm2->addPersonWithRole($personBob, $rolePresenter);
        $mm2->addPersonWithRole($personJohn, $rolePresenter);

        $mm3 = new MultimediaObject();
        $title3 = 'Multimedia Object 3';
        $mm3->setTitle($title3);
        $mm3->addPersonWithRole($personJohn, $roleActor);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->flush();
      
        $this->assertNull($this->personService->findPersonById($personJohn->getId())->getEmail());
        $this->assertNull($this->personService->findPersonById($personBob->getId())->getEmail());
        $this->assertNull($mm1->getPersonWithRole($personJohn, $roleActor)->getEmail());
        $this->assertNull($mm1->getPersonWithRole($personJohn, $rolePresenter)->getEmail());
        $this->assertNull($mm1->getPersonWithRole($personBob, $roleActor)->getEmail());
        $this->assertNull($mm2->getPersonWithRole($personJohn, $roleActor)->getEmail());
        $this->assertNull($mm2->getPersonWithRole($personBob, $rolePresenter)->getEmail());
        $this->assertNull($mm2->getPersonWithRole($personJohn, $rolePresenter)->getEmail());
        $this->assertNull($mm3->getPersonWithRole($personJohn, $roleActor)->getEmail());
        
        $emailJohn = 'johnsmith@mail.com';
        $personJohn->setEmail($emailJohn);

        $personJohn = $this->personService->updatePerson($personJohn);

        $this->assertEquals($emailJohn, $this->personService->findPersonById($personJohn->getId())->getEmail());
        $this->assertNull($this->personService->findPersonById($personBob->getId())->getEmail());
        $this->assertEquals($emailJohn, $mm1->getPersonWithRole($personJohn, $roleActor)->getEmail());
        $this->assertEquals($emailJohn, $mm1->getPersonWithRole($personJohn, $rolePresenter)->getEmail());
        $this->assertNull($mm1->getPersonWithRole($personBob, $roleActor)->getEmail());
        $this->assertEquals($emailJohn, $mm2->getPersonWithRole($personJohn, $roleActor)->getEmail());
        $this->assertNull($mm2->getPersonWithRole($personBob, $rolePresenter)->getEmail());
        $this->assertEquals($emailJohn, $mm2->getPersonWithRole($personJohn, $rolePresenter)->getEmail());
        $this->assertEquals($emailJohn, $mm3->getPersonWithRole($personJohn, $roleActor)->getEmail());

        $emailBob = 'bobclark@mail.com';
        $personBob->setEmail($emailBob);

        $personBob = $this->personService->updatePerson($personBob);

        $this->assertEquals($emailJohn, $this->personService->findPersonById($personJohn->getId())->getEmail());
        $this->assertEquals($emailBob, $this->personService->findPersonById($personBob->getId())->getEmail());
        $this->assertEquals($emailJohn, $mm1->getPersonWithRole($personJohn, $roleActor)->getEmail());
        $this->assertEquals($emailJohn, $mm1->getPersonWithRole($personJohn, $rolePresenter)->getEmail());
        $this->assertEquals($emailBob, $mm1->getPersonWithRole($personBob, $roleActor)->getEmail());
        $this->assertEquals($emailJohn, $mm2->getPersonWithRole($personJohn, $roleActor)->getEmail());
        $this->assertEquals($emailBob, $mm2->getPersonWithRole($personBob, $rolePresenter)->getEmail());
        $this->assertEquals($emailJohn, $mm2->getPersonWithRole($personJohn, $rolePresenter)->getEmail());
        $this->assertEquals($emailJohn, $mm3->getPersonWithRole($personJohn, $roleActor)->getEmail());

    }
}