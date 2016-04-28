<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Group;

class GroupTest extends \PHPUnit_Framework_TestCase
{
    public function testSetterAndGetter()
    {
        $group = new Group();

        $key = 'GROUPA';
        $name = "Group A";
        $updatedAt = new \Datetime('now');

        $group->setKey($key);
        $group->setName($name);
        $group->setUpdatedAt($updatedAt);

        $this->assertEquals($key, $group->getKey());
        $this->assertEquals($name, $group->getName());
        $this->assertEquals($updatedAt, $group->getUpdatedAt());
    }
}