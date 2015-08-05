<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\User;

class UserTest extends \PHPUnit_Framework_TestCase
{
    public function testSetterAndGetter()
    {
        $fullname = "fullname";

        $user = new User();

        $user->setFullname($fullname);
        $this->assertEquals($fullname, $user->getFullname());
    }
}