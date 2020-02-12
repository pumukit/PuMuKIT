<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use MongoDB\BSON\ObjectId;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Group;

/**
 * @internal
 * @coversNothing
 */
class GroupRepositoryTest extends PumukitTestCase
{
    private $repo;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(Group::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        gc_collect_cycles();
    }

    public function testRepositoryEmpty(): void
    {
        $this->assertCount(0, $this->repo->findAll());
    }

    public function testRepository(): void
    {
        $group = new Group();

        $group->setKey('GroupA');
        $group->setName('Group A');

        $this->dm->persist($group);
        $this->dm->flush();

        $this->assertCount(1, $this->repo->findAll());
    }

    public function testFindByIdNotIn(): void
    {
        $group1 = new Group();
        $group1->setKey('Group1');
        $group1->setName('Group 1');

        $group2 = new Group();
        $group2->setKey('Group2');
        $group2->setName('Group 2');

        $group3 = new Group();
        $group3->setKey('Group3');
        $group3->setName('Group 3');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($group3);
        $this->dm->flush();

        $ids = [new ObjectId($group1->getId()), new ObjectId($group3->getId())];
        $groups = $this->repo->findByIdNotIn($ids)->toArray();
        $this->assertNotContains($group1, $groups);
        $this->assertContains($group2, $groups);
        $this->assertNotContains($group3, $groups);
    }

    public function testFindByIdNotInOf(): void
    {
        $group1 = new Group();
        $group1->setKey('Group1');
        $group1->setName('Group 1');

        $group2 = new Group();
        $group2->setKey('Group2');
        $group2->setName('Group 2');

        $group3 = new Group();
        $group3->setKey('Group3');
        $group3->setName('Group 3');

        $group4 = new Group();
        $group4->setKey('Group4');
        $group4->setName('Group 4');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($group3);
        $this->dm->persist($group4);
        $this->dm->flush();

        $ids = [new ObjectId($group1->getId()), new ObjectId($group3->getId())];
        $total = [new ObjectId($group1->getId()), new ObjectId($group3->getId()), new ObjectId($group4->getId())];
        $groups = $this->repo->findByIdNotInOf($ids, $total)->toArray();
        $this->assertNotContains($group1, $groups);
        $this->assertNotContains($group2, $groups);
        $this->assertNotContains($group3, $groups);
        $this->assertContains($group4, $groups);

        $ids = [];
        $total = [new ObjectId($group1->getId()), new ObjectId($group3->getId()), new ObjectId($group4->getId())];
        $groups = $this->repo->findByIdNotInOf($ids, $total)->toArray();
        $this->assertContains($group1, $groups);
        $this->assertNotContains($group2, $groups);
        $this->assertContains($group3, $groups);
        $this->assertContains($group4, $groups);

        $ids = [new ObjectId($group1->getId()), new ObjectId($group3->getId())];
        $total = [];
        $groups = $this->repo->findByIdNotInOf($ids, $total)->toArray();
        $this->assertNotContains($group1, $groups);
        $this->assertNotContains($group2, $groups);
        $this->assertNotContains($group3, $groups);
        $this->assertNotContains($group4, $groups);

        $ids = [];
        $total = [];
        $groups = $this->repo->findByIdNotInOf($ids, $total)->toArray();
        $this->assertNotContains($group1, $groups);
        $this->assertNotContains($group2, $groups);
        $this->assertNotContains($group3, $groups);
        $this->assertNotContains($group4, $groups);
    }
}
