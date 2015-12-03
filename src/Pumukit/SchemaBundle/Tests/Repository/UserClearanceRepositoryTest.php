<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Clearance;
use Pumukit\SchemaBundle\Document\UserClearance;

class UserClearanceRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();
        $this->dm = $kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository('PumukitSchemaBundle:UserClearance');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:UserClearance')
            ->remove(array());
    }

    public function testEmpty()
    {
        $this->assertEmpty($this->repo->findAll());
    }

    public function testRepository()
    {
        $this->assertCount(0, $this->repo->findAll());

        $userClearance = new UserClearance();
        $userClearance->setName('test');

        $this->dm->persist($userClearance);
        $this->dm->flush();

        $this->assertCount(1, $this->repo->findAll());

        $this->assertEquals($userClearance, $this->repo->find($userClearance->getId()));
   }
}