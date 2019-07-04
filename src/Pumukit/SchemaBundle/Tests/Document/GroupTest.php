<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Group;
use PHPUnit\Framework\TestCase;

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

        $this->assertEquals($key, $group->getKey());
        $this->assertEquals($key, (string) $group);
        $this->assertEquals($name, $group->getName());
        $this->assertEquals($comments, $group->getComments());
        $this->assertEquals($origin, $group->getOrigin());
        $this->assertEquals($updatedAt, $group->getUpdatedAt());
    }

    public function testGroupInterface()
    {
        $group = new Group();

        $key = 'GROUPA';
        $name = 'Group A';

        $group->setKey($key);
        $group->setName($name);

        $this->assertEquals($group, $group->addRole('role_test'));
        $this->assertFalse($group->hasRole('role_test'));
        $this->assertEquals([], $group->getRoles());
        $this->assertEquals($group, $group->removeRole('role_test'));
        $this->assertEquals($group, $group->setRoles([]));
    }
}
