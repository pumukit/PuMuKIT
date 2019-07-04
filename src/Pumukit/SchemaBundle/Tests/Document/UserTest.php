<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\User;

/**
 * @internal
 * @coversNothing
 */
class UserTest extends TestCase
{
    public function testSetterAndGetter()
    {
        $username = 'username';
        $fullname = 'fullname';
        $origin1 = User::ORIGIN_LOCAL;

        $user = new User();

        $user->setUsername($username);
        $user->setFullname($fullname);
        $user->setOrigin($origin1);
        $this->assertEquals($fullname, $user->getFullname());
        $this->assertEquals($origin1, $user->getOrigin());
        $this->assertTrue($user->isLocal());

        $origin2 = 'ldap';
        $user->setOrigin($origin2);
        $this->assertEquals($origin2, $user->getOrigin());
        $this->assertFalse($user->isLocal());
    }
}
