<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Group;

/**
 * @internal
 * @coversNothing
 */
class GroupTest extends TestCase
{
    public function testSetterAndGetter()
    {
        $group = new Group();

        $key = 'GROUPA';
        $name = 'Group A';
        $comments = 'Group created to test setter and getter';
        $origin = Group::ORIGIN_LOCAL;
        $updatedAt = new \DateTime();

        $group->setKey($key);
        $group->setName($name);
        $group->setComments($comments);
        $group->setOrigin($origin);
        $group->setUpdatedAt($updatedAt);

        static::assertEquals($key, $group->getKey());
        static::assertEquals($key, (string) $group);
        static::assertEquals($name, $group->getName());
        static::assertEquals($comments, $group->getComments());
        static::assertEquals($origin, $group->getOrigin());
        static::assertEquals($updatedAt, $group->getUpdatedAt());
    }

    public function testGroupInterface()
    {
        $group = new Group();

        $key = 'GROUPA';
        $name = 'Group A';

        $group->setKey($key);
        $group->setName($name);

        static::assertEquals($group, $group->addRole('role_test'));
        static::assertFalse($group->hasRole('role_test'));
        static::assertEquals([], $group->getRoles());
        static::assertEquals($group, $group->removeRole('role_test'));
        static::assertEquals($group, $group->setRoles([]));
    }
}
