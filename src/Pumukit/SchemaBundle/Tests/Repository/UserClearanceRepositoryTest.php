<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Security\Clearance;
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

    public function testChangeDefault()
    {
        $this->assertCount(0, $this->repo->findByDefault(true));
        $this->assertCount(0, $this->repo->findByDefault(false));

        $userClearance1 = new UserClearance();
        $userClearance1->setName('test1');
        $userClearance1->setDefault(true);

        $userClearance2 = new UserClearance();
        $userClearance2->setName('test2');
        $userClearance2->setDefault(false);

        $userClearance3 = new UserClearance();
        $userClearance3->setName('test3');
        $userClearance3->setDefault(false);

        $this->dm->persist($userClearance1);
        $this->dm->persist($userClearance2);
        $this->dm->persist($userClearance3);
        $this->dm->flush();

        $this->assertCount(1, $this->repo->findByDefault(true));
        $this->assertCount(2, $this->repo->findByDefault(false));

        $this->repo->changeDefault();

        $this->assertCount(0, $this->repo->findByDefault(true));
        $this->assertCount(3, $this->repo->findByDefault(false));

        $this->repo->changeDefault(false);

        $this->assertCount(3, $this->repo->findByDefault(true));
        $this->assertCount(0, $this->repo->findByDefault(false));

        $this->repo->changeDefault(true);

        $this->assertCount(0, $this->repo->findByDefault(true));
        $this->assertCount(3, $this->repo->findByDefault(false));
    }
}