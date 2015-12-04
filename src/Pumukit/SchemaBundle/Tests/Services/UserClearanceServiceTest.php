<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Security\Clearance;
use Pumukit\SchemaBundle\Document\UserClearance;
use Pumukit\SchemaBundle\Services\UserClearanceService;

class UserClearanceServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $userClearanceService;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
          ->getRepository('PumukitSchemaBundle:UserClearance');
        $this->userClearanceService = $kernel->getContainer()
          ->get('pumukitschema.userclearance');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:UserClearance')->remove(array());
        $this->dm->flush();

        $this->userClearanceService = new UserClearanceService($this->dm);
    }

    public function testUpdate()
    {
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

        $this->assertEquals($userClearance1, $this->repo->findOneByDefault(true));

        $falseDefault = $this->repo->findByDefault(false);
        $this->assertFalse(in_array($userClearance1, $falseDefault));
        $this->assertTrue(in_array($userClearance2, $falseDefault));
        $this->assertTrue(in_array($userClearance3, $falseDefault));

        $userClearance2->setDefault(true);
        $userClearance2 = $this->userClearanceService->update($userClearance2);

        $this->assertEquals($userClearance2, $this->repo->findOneByDefault(true));

        $falseDefault = $this->repo->findByDefault(false);
        $this->assertTrue(in_array($userClearance1, $falseDefault));
        $this->assertFalse(in_array($userClearance2, $falseDefault));
        $this->assertTrue(in_array($userClearance3, $falseDefault));
    }

    public function testAddClearance()
    {
        $clearances = array(
                            Clearance::ACCESS_DASHBOARD,
                            Clearance::ACCESS_MULTIMEDIA_SERIES
                            );

        $userClearance = new UserClearance();
        $userClearance->setName('test');
        $userClearance->setClearances($clearances);

        $this->dm->persist($userClearance);
        $this->dm->flush();

        $this->assertEquals($clearances, $userClearance->getClearances());

        $this->userClearanceService->addClearance($userClearance, 'NON_EXISTING_CLEARANCE');
        $this->assertEquals($clearances, $userClearance->getClearances());

        $this->userClearanceService->addClearance($userClearance, Clearance::ACCESS_ROLES);

        $newClearances = array(
                               Clearance::ACCESS_DASHBOARD,
                               Clearance::ACCESS_MULTIMEDIA_SERIES,
                               Clearance::ACCESS_ROLES
                               );

        $falseClearances = array(
                                 Clearance::ACCESS_DASHBOARD,
                                 Clearance::ACCESS_MULTIMEDIA_SERIES,
                                 Clearance::ACCESS_LIVE_EVENTS
                                 );

        $this->assertEquals($newClearances, $userClearance->getClearances());
        $this->assertNotEquals($falseClearances, $userClearance->getClearances());
    }

    public function testRemoveClearance()
    {
        $clearances = array(
                            Clearance::ACCESS_DASHBOARD,
                            Clearance::ACCESS_MULTIMEDIA_SERIES
                            );

        $userClearance = new UserClearance();
        $userClearance->setName('test');
        $userClearance->setClearances($clearances);

        $this->dm->persist($userClearance);
        $this->dm->flush();

        $this->assertEquals($clearances, $userClearance->getClearances());

        $this->userClearanceService->removeClearance($userClearance, 'NON_EXISTING_CLEARANCE');
        $this->assertEquals($clearances, $userClearance->getClearances());

        $this->userClearanceService->removeClearance($userClearance, Clearance::ACCESS_MULTIMEDIA_SERIES);

        $newClearances = array(Clearance::ACCESS_DASHBOARD);

        $this->assertEquals($newClearances, $userClearance->getClearances());
        $this->assertNotEquals($clearances, $userClearance->getClearances());
    }

    public function testCheckDefault()
    {
        $clearances1 = array(Clearance::ACCESS_DASHBOARD);
        $userClearance1 = new UserClearance();
        $userClearance1->setName('test1');
        $userClearance1->setDefault(true);
        $userClearance1->setClearances($clearances1);

        $clearances2 = array(Clearance::ACCESS_DASHBOARD, Clearance::ACCESS_ADVANCED_UPLOAD);
        $userClearance2 = new UserClearance();
        $userClearance2->setName('test2');
        $userClearance2->setDefault(false);
        $userClearance2->setClearances($clearances2);

        $clearances3 = array();
        $userClearance3 = new UserClearance();
        $userClearance3->setName('test3');
        $userClearance3->setDefault(false);
        $userClearance3->setClearances($clearances3);

        $this->dm->persist($userClearance1);
        $this->dm->persist($userClearance2);
        $this->dm->persist($userClearance3);
        $this->dm->flush();

        $this->assertEquals($userClearance1, $this->repo->findOneByDefault(true));

        $falseDefault = $this->repo->findByDefault(false);
        $this->assertFalse(in_array($userClearance1, $falseDefault));
        $this->assertTrue(in_array($userClearance2, $falseDefault));
        $this->assertTrue(in_array($userClearance3, $falseDefault));

        $userClearance1->setDefault(false);
        $userClearance1 = $this->userClearanceService->update($userClearance1);

        $this->assertEquals($userClearance3, $this->repo->findOneByDefault(true));

        $falseDefault = $this->repo->findByDefault(false);
        $this->assertTrue(in_array($userClearance1, $falseDefault));
        $this->assertTrue(in_array($userClearance2, $falseDefault));
        $this->assertFalse(in_array($userClearance3, $falseDefault));
    }

    public function testSetDefaultUserClearance()
    {
        $this->assertFalse($this->userClearanceService->setDefaultUserClearance());

        $clearances1 = array(Clearance::ACCESS_DASHBOARD);
        $userClearance1 = new UserClearance();
        $userClearance1->setName('test1');
        $userClearance1->setDefault(false);
        $userClearance1->setClearances($clearances1);

        $clearances2 = array();
        $userClearance2 = new UserClearance();
        $userClearance2->setName('test2');
        $userClearance2->setDefault(false);
        $userClearance2->setClearances($clearances2);

        $clearances3 = array(Clearance::ACCESS_DASHBOARD, Clearance::ACCESS_ADVANCED_UPLOAD);
        $userClearance3 = new UserClearance();
        $userClearance3->setName('test3');
        $userClearance3->setDefault(false);
        $userClearance3->setClearances($clearances3);

        $this->dm->persist($userClearance1);
        $this->dm->persist($userClearance2);
        $this->dm->persist($userClearance3);
        $this->dm->flush();

        $this->assertEquals($userClearance2, $this->userClearanceService->setDefaultUserClearance());
    }
}