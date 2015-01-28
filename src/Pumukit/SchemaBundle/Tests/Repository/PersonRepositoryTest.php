<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Person;

class PersonRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;

    public function setUp()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();
        $this->dm = $kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository('PumukitSchemaBundle:Person');

        //DELETE DATABASE
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Person')->remove(array());
        $this->dm->flush();
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

    private function createNewPerson()
    {
        $email = 'email@email.com';
        $name = 'name';
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
}
