<?php

namespace Pumukit\SchemaBundle\Tests\Entity;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Pumukit\SchemaBundle\Entity\Person;

class PersonRepositoryTest extends WebTestCase
{

    private $em;
    private $repo;

    public function setUp()
    {
        //INIT TEST SUITE
        $kernel = static::createKernel();
        $kernel->boot();
        $this->em = $kernel->getContainer()
            ->get('doctrine.orm.entity_manager');
        $this->repo = $this->em
            ->getRepository('PumukitSchemaBundle:Person');

        //DELETE DATABASE
        $this->em->createQuery("DELETE PumukitSchemaBundle:Person p")->getResult();
    }

    public function testRepository()
    {/*
        $login     = "login";
        $password  = '123<>U45;';
        $name      = "Login Gomez Gomez";
        $email     = "login@pr.es";
        $web       = 'http://www.url.com';
        $phone     = '+34986123456';
        $honorific = 'honorific';
        $firm      = 'firm';
        $post      = 'post';

        $user = new Person();
        $user->setLogin($login);
        $user->setPassword($password);
        $user->setName($name);
        $user->setEmail($email);

        $this->em->persist($user);
        $this->em->flush();

        // This should pass to check the unrequired fields
        $this->assertEquals(1, count($this->repo->findAll()));
        */
    }
}
