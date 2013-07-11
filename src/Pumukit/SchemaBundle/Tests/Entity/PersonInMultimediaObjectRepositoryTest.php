<?php

namespace Pumukit\SchemaBundle\Tests\Entity;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Pumukit\SchemaBundle\Entity\MultimediaObject;
use Pumukit\SchemaBundle\Entity\Person;
use Pumukit\SchemaBundle\Entity\PersonInMultimediaObject;


class PersonInMultimediaObjectRepositoryTest extends WebTestCase
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
            ->getRepository('PumukitSchemaBundle:PersonInMultimediaObject');

       
        //DELETE DATABASE
        $this->em->createQuery("DELETE PumukitSchemaBundle:PersonInMultimediaObject pimo")->getResult();
    }
    
    public function testRepository()
    {
        
    }
}