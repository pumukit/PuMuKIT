<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Pumukit\SchemaBundle\Document\Role;

class RoleRepositoryTest extends WebTestCase
{

    private $dm;
    private $repo;

    public function setUp()
    {
        //INIT TEST SUITE
        $kernel = static::createKernel();
        $kernel->boot();
        $this->dm = $kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository('PumukitSchemaBundle:Role');

        //DELETE DATABASE
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Role')->remove(array());
        $this->dm->flush();
    }

    public function testRepository()
    {
        $cod = 123;
        $rank = 5;
        $xml = '<xml contenido del xml/>';
        $display = true;
        $name = 'rol1';
        $text = 'Bruno ha venido a ver cómo funciona esto';
        
        $rol = new Role();
        $rol->setCod($cod);
        $rol->setRank($rank);
        $rol->setXml($xml);
        $rol->setDisplay($display); // true by default
        $rol->setName($name);
        $rol->setText($text);

        $this->dm->persist($rol);
        $this->dm->flush();

        // This should pass to check the unrequired fields
        $this->assertEquals(1, count($this->repo->findAll()));
        
    }
}
