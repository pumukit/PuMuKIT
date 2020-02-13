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
        static::assertEquals($fullname, $user->getFullname());
        static::assertEquals($origin1, $user->getOrigin());
        static::assertTrue($user->isLocal());

        $origin2 = 'ldap';
        $user->setOrigin($origin2);
        static::assertEquals($origin2, $user->getOrigin());
        static::assertFalse($user->isLocal());
    }
}
