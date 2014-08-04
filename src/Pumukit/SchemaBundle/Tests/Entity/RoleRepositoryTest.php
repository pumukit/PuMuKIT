<?php

namespace Pumukit\SchemaBundle\Tests\Entity;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Pumukit\SchemaBundle\Entity\Role;
use Pumukit\SchemaBundle\Entity\PersonInMultimediaObject;

class RoleRepositoryTest extends WebTestCase
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
            ->getRepository('PumukitSchemaBundle:Role');

        //DELETE DATABASE
        $this->em->createQuery("DELETE PumukitSchemaBundle:Role r")->getResult();
    }

    public function testRepository()
    {
        /*
        $cod     = 123;
        $rank    = 5;
        $xml     = '<xml la lala la lala la lala laaaa/>';
        $display = true;
        $name    = 'rol1';
        $text    = 'Tú tenías mucha razón, ¡no te hice caaaasooo! Hoy he de
         reconocer, ¡delante de un vaaasooo! Hoy me pesa la cabezaaaa,
         ¡qué pesaaaaaar! ¡Te juro que nesesiiiiiitoooooo
         reeeeee-greeeeee-saaaaaaar...!';
        $pimo    = new PersonInMultimediaObject();

        $rol = new Role();
        $rol->setCod($cod);
        $rol->setRank($rank);
        $rol->setXml($xml);
        // $rol->setDisplay($display); // true by default
        $rol->setName($name);
        $rol->setText($text);

        $this->em->persist($rol);
        $this->em->flush();

        // This should pass to check the unrequired fields
        $this->assertEquals(1, count($this->repo->findAll()));
        */
    }
}
