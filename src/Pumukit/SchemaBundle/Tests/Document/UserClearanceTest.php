<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\UserClearance;
use Pumukit\SchemaBundle\Document\Clearance;

class UserClearanceTest extends \PHPUnit_Framework_TestCase
{
    public function testSetterAndGetter()
    {
        $name = 'User Test Clearance';
        $clearances = array(
                            Clearance::ACCESS_DASHBOARD,
                            Clearance::ACCESS_MULTIMEDIA_SERIES,
                            Clearance::ACCESS_LIVE_CHANNELS,
                            Clearance::ACCESS_LIVE_EVENTS,
                            Clearance::ACCESS_INGESTOR
                            );
        $system = true;
        $default = true;
        $scope = UserClearance::SCOPE_GLOBAL;

        $userClearance = new UserClearance();

        $userClearance->setName($name);
        $userClearance->setClearances($clearances);
        $userClearance->setSystem($system);
        $userClearance->setDefault($default);
        $userClearance->setScope($scope);

        $this->assertEquals($name, $userClearance->getName());
        $this->assertEquals($clearances, $userClearance->getClearances());
        $this->assertEquals($system, $userClearance->getSystem());
        $this->assertEquals($default, $userClearance->getDefault());
        $this->assertEquals($scope, $userClearance->getScope());
    }

    public function testClearancesCollection()
    {
        $name = 'User Test Clearance';
        $clearances = array(
                            Clearance::ACCESS_DASHBOARD,
                            Clearance::ACCESS_MULTIMEDIA_SERIES,
                            Clearance::ACCESS_LIVE_CHANNELS,
                            Clearance::ACCESS_LIVE_EVENTS,
                            Clearance::ACCESS_INGESTOR
                            );
        $system = true;
        $default = true;
        $scope = UserClearance::SCOPE_GLOBAL;

        $userClearance = new UserClearance();

        $userClearance->setName($name);
        $userClearance->setClearances($clearances);
        $userClearance->setSystem($system);
        $userClearance->setDefault($default);
        $userClearance->setScope($scope);

        $this->assertEquals($clearances, $userClearance->getClearances());

        $this->assertTrue($userClearance->containsClearance(Clearance::ACCESS_DASHBOARD));
        $this->assertFalse($userClearance->containsClearance(Clearance::ACCESS_ADMIN_USERS));

        $this->assertTrue($userClearance->containsAllClearances($clearances));

        $moreClearances = array(
                                 Clearance::ACCESS_DASHBOARD,
                                 Clearance::ACCESS_MULTIMEDIA_SERIES,
                                 Clearance::ACCESS_ADMIN_USERS
                                 );

        $fewerClearances = array(
                                 Clearance::ACCESS_DASHBOARD,
                                 Clearance::ACCESS_MULTIMEDIA_SERIES
                                 );

        $notClearances = array(
                               Clearance::ACCESS_ADMIN_USERS,
                               Clearance::ACCESS_ROLES
                               );

        $this->assertFalse($userClearance->containsAllClearances($moreClearances));
        $this->assertTrue($userClearance->containsAllClearances($fewerClearances));
        $this->assertTrue($userClearance->containsAnyClearance($fewerClearances));
        $this->assertTrue($userClearance->containsAnyClearance($moreClearances));
        $this->assertFalse($userClearance->containsAnyClearance($notClearances));

        $newClearances = array(
                            Clearance::ACCESS_DASHBOARD,
                            Clearance::ACCESS_MULTIMEDIA_SERIES,
                            Clearance::ACCESS_LIVE_CHANNELS,
                            Clearance::ACCESS_LIVE_EVENTS,
                            Clearance::ACCESS_INGESTOR,
                            Clearance::ACCESS_ADMIN_USERS
                            );

        $this->assertEquals($newClearances, $userClearance->addClearance(Clearance::ACCESS_ADMIN_USERS));
        $this->assertTrue($userClearance->containsClearance(Clearance::ACCESS_ADMIN_USERS));

        $this->assertTrue($userClearance->removeClearance(Clearance::ACCESS_DASHBOARD));
        $this->assertFalse($userClearance->containsClearance(Clearance::ACCESS_DASHBOARD));

        $this->assertFalse($userClearance->removeClearance(Clearance::UPLOAD_WITHOUT_WIZARD));
    }

}