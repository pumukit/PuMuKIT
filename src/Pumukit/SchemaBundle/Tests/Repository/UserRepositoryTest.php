<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Repository;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\User;

/**
 * @internal
 *
 * @coversNothing
 */
class UserRepositoryTest extends PumukitTestCase
{
    private $repo;
    private $groupRepo;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(User::class);
        $this->groupRepo = $this->dm->getRepository(Group::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        gc_collect_cycles();
    }

    public function testRepositoryEmpty()
    {
        static::assertCount(0, $this->repo->findAll());
    }

    public function testRepository()
    {
        $user = new User();

        $user->setEmail('test@mail.com');
        $user->setUsername('test');

        $this->dm->persist($user);
        $this->dm->flush();

        static::assertCount(1, $this->repo->findAll());
    }

    public function testPerson()
    {
        $person = new Person();
        $user = new User();

        $this->dm->persist($person);
        $this->dm->persist($user);
        $this->dm->flush();

        $person->setUser($user);
        $user->setPerson($person);

        $this->dm->persist($person);
        $this->dm->persist($user);
        $this->dm->flush();

        $user = $this->repo->find($user->getId());

        static::assertEquals($person, $user->getPerson());
    }

    public function testPermissionProfile()
    {
        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName('test');
        $user = new User();

        $this->dm->persist($permissionProfile);
        $this->dm->persist($user);
        $this->dm->flush();

        $user->setPermissionProfile($permissionProfile);

        $this->dm->persist($user);
        $this->dm->flush();

        $user = $this->repo->find($user->getId());

        static::assertEquals($permissionProfile, $user->getPermissionProfile());
    }

    public function testUserGroups()
    {
        static::assertCount(0, $this->groupRepo->findAll());

        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        static::assertCount(1, $this->groupRepo->findAll());

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        static::assertCount(2, $this->groupRepo->findAll());

        $user = new User();
        $user->setEmail('testgroup@mail.com');
        $user->setUsername('testgroup');
        $user->addGroup($group1);

        $this->dm->persist($user);
        $this->dm->flush();

        static::assertTrue($user->containsGroup($group1));
        static::assertFalse($user->containsGroup($group2));
        static::assertCount(1, $user->getGroups());

        $user->addGroup($group2);

        $this->dm->persist($user);
        $this->dm->flush();

        static::assertTrue($user->containsGroup($group1));
        static::assertTrue($user->containsGroup($group2));
        static::assertCount(2, $user->getGroups());

        $user->removeGroup($group1);

        $this->dm->persist($user);
        $this->dm->flush();

        static::assertFalse($user->containsGroup($group1));
        static::assertTrue($user->containsGroup($group2));
        static::assertCount(1, $user->getGroups());

        static::assertCount(2, $this->groupRepo->findAll());
    }

    public function testGetGroupsIds()
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $user = new User();
        $user->setEmail('testgroup@mail.com');
        $user->setUsername('testgroup');

        $this->dm->persist($user);
        $this->dm->flush();

        static::assertCount(0, $user->getGroupsIds());

        $user->addGroup($group1);

        $this->dm->persist($user);
        $this->dm->flush();

        static::assertCount(1, $user->getGroupsIds());

        $user->addGroup($group2);

        $this->dm->persist($user);
        $this->dm->flush();

        static::assertCount(2, $user->getGroupsIds());
    }

    private function createGroup($key = 'Group1', $name = 'Group 1')
    {
        $group = new Group();

        $group->setKey($key);
        $group->setName($name);

        $this->dm->persist($group);
        $this->dm->flush();

        return $group;
    }
}
