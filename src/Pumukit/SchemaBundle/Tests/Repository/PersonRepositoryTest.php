<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class PersonRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $factoryService;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository(Person::class);
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');

        //DELETE DATABASE
        $this->dm->getDocumentCollection(MultimediaObject::class)
            ->remove([]);
        $this->dm->getDocumentCollection(Role::class)
            ->remove([]);
        $this->dm->getDocumentCollection(Person::class)
            ->remove([]);
        $this->dm->getDocumentCollection(Series::class)
            ->remove([]);
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
        $person = $this->createNewPerson();

        $this->assertEquals(1, count($this->repo->findAll()));
    }

    public function testUser()
    {
        $person = new Person();
        $user = new User();

        $this->dm->persist($person);
        $this->dm->persist($user);
        $this->dm->flush();

        $person->setUser($user);
        $user->setPerson($person);

        $this->dm->persist($person);
        $this->dm->persist($user);
        $this->dm->flush();

        $person = $this->repo->find($person->getId());

        $this->assertEquals($user, $person->getUser());
    }

    public function testFindByRoleCodAndEmail()
    {
        $series_main = $this->createSeries("Stark's growing pains");
        $series_wall = $this->createSeries('The Wall');
        $series_lhazar = $this->createSeries('A quiet life');

        $this->dm->persist($series_main);
        $this->dm->persist($series_wall);
        $this->dm->persist($series_lhazar);
        $this->dm->flush();

        $email_ned = 'ned@email.com';
        $person_ned = $this->createNewPerson('Ned', $email_ned);
        $email_benjen = 'benjen@email.com';
        $person_benjen = $this->createNewPerson('Benjen', $email_benjen);
        $email_mark = 'mark@email.com';
        $person_mark = $this->createNewPerson('Mark', $email_mark);

        $role_lord = $this->createRole('Lord');
        $role_ranger = $this->createRole('Ranger');
        $role_hand = $this->createRole('Hand');

        $mm1 = $this->factoryService->createMultimediaObject($series_main);
        $mm2 = $this->factoryService->createMultimediaObject($series_wall);
        $mm3 = $this->factoryService->createMultimediaObject($series_main);
        $mm4 = $this->factoryService->createMultimediaObject($series_lhazar);

        $mm1->addPersonWithRole($person_ned, $role_lord);
        $mm1->addPersonWithRole($person_mark, $role_lord);
        $mm1->addPersonWithRole($person_benjen, $role_lord);
        $mm1->addPersonWithRole($person_ned, $role_ranger);
        $mm2->addPersonWithRole($person_ned, $role_lord);
        $mm2->addPersonWithRole($person_ned, $role_ranger);
        $mm2->addPersonWithRole($person_benjen, $role_ranger);
        $mm2->addPersonWithRole($person_mark, $role_hand);
        $mm3->addPersonWithRole($person_ned, $role_lord);
        $mm3->addPersonWithRole($person_benjen, $role_ranger);
        $mm4->addPersonWithRole($person_mark, $role_ranger);
        $mm4->addPersonWithRole($person_ned, $role_hand);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->persist($mm4);
        $this->dm->flush();

        $this->assertEquals($person_ned, $this->repo->findByRoleCodAndEmail($role_lord->getCod(), $email_ned));
        $this->assertEquals($person_mark, $this->repo->findByRoleCodAndEmail($role_lord->getCod(), $email_mark));
        $this->assertEquals($person_benjen, $this->repo->findByRoleCodAndEmail($role_lord->getCod(), $email_benjen));
        $this->assertEquals($person_ned, $this->repo->findByRoleCodAndEmail($role_ranger->getCod(), $email_ned));
        $this->assertEquals($person_mark, $this->repo->findByRoleCodAndEmail($role_ranger->getCod(), $email_mark));
        $this->assertEquals($person_benjen, $this->repo->findByRoleCodAndEmail($role_ranger->getCod(), $email_benjen));
        $this->assertEquals($person_ned, $this->repo->findByRoleCodAndEmail($role_hand->getCod(), $email_ned));
        $this->assertEquals($person_mark, $this->repo->findByRoleCodAndEmail($role_hand->getCod(), $email_mark));
        $this->assertNull($this->repo->findByRoleCodAndEmail($role_hand->getCod(), $email_benjen));
    }

    private function createNewPerson($name = 'name', $email = 'email@email.com')
    {
        $web = 'web';
        $phone = 'phone';
        $honorific = 'Mr';
        $firm = 'firm';
        $post = 'post';
        $bio = 'Biography of this person';

        $person = new Person();

        $person->setEmail($email);
        $person->setName($name);
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
