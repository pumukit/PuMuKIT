<?php

namespace Pumukit\SchemaBundle\Tests\Other;

use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class RemoveElementTest extends WebTestCase
{
    private $dm;
    private $mmRepo;
    private $groupRepo;
    private $factoryService;
    private $mmService;
    private $userService;
    private $ebService;
    private $groupService;
    private $repo;
    private $mmsPicService;
    private $tagService;
    private $userRepo;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->mmRepo = $this->dm
            ->getRepository(MultimediaObject::class)
        ;
        $this->userRepo = $this->dm
            ->getRepository(User::class)
        ;
        $this->groupRepo = $this->dm
            ->getRepository(Group::class)
        ;
        $this->factoryService = static::$kernel->getContainer()
            ->get('pumukitschema.factory')
        ;
        $this->mmService = static::$kernel->getContainer()
            ->get('pumukitschema.multimedia_object')
        ;
        $this->userService = static::$kernel->getContainer()
            ->get('pumukitschema.user')
        ;
        $this->ebService = static::$kernel->getContainer()
            ->get('pumukitschema.embeddedbroadcast')
        ;
        $this->groupService = static::$kernel->getContainer()
            ->get('pumukitschema.group')
        ;

        //DELETE DATABASE
        $this->dm->getDocumentCollection(MultimediaObject::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection(Group::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection(User::class)
            ->remove([])
        ;
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        $this->factoryService = null;
        $this->mmsPicService = null;
        $this->tagService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testMultimediaObjectRemoveGroupDocument()
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $mm1 = new MultimediaObject();
        $mm1->setTitle('test');
        $mm1->addGroup($group1);

        $mm2 = new MultimediaObject();
        $mm2->setTitle('test');
        $mm2->addGroup($group1);
        $mm2->addGroup($group2);

        $mm3 = new MultimediaObject();
        $mm3->setTitle('test');
        $mm3->addGroup($group1);

        $mm4 = new MultimediaObject();
        $mm4->setTitle('test');
        $mm4->addGroup($group1);
        $mm4->addGroup($group2);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->persist($mm4);
        $this->dm->flush();

        $this->assertTrue($mm1->containsGroup($group1));
        $this->assertFalse($mm1->containsGroup($group2));
        $this->assertTrue($mm2->containsGroup($group1));
        $this->assertTrue($mm2->containsGroup($group2));
        $this->assertTrue($mm3->containsGroup($group1));
        $this->assertFalse($mm3->containsGroup($group2));
        $this->assertTrue($mm4->containsGroup($group1));
        $this->assertTrue($mm4->containsGroup($group2));
        $this->assertEquals(1, $mm1->getGroups()->count());
        $this->assertEquals(2, $mm2->getGroups()->count());
        $this->assertEquals(1, $mm3->getGroups()->count());
        $this->assertEquals(2, $mm4->getGroups()->count());

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $mm1->removeGroup($group1);
        $mm2->removeGroup($group1);
        $mm3->removeGroup($group1);
        $mm4->removeGroup($group1);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->persist($mm4);
        $this->dm->flush();

        $this->assertFalse($mm1->containsGroup($group1));
        $this->assertFalse($mm1->containsGroup($group2));
        $this->assertFalse($mm2->containsGroup($group1));
        $this->assertTrue($mm2->containsGroup($group2));
        $this->assertFalse($mm3->containsGroup($group1));
        $this->assertFalse($mm3->containsGroup($group2));
        $this->assertFalse($mm4->containsGroup($group1));
        $this->assertTrue($mm4->containsGroup($group2));
        $this->assertEquals(0, $mm1->getGroups()->count());
        $this->assertEquals(1, $mm2->getGroups()->count());
        $this->assertEquals(0, $mm3->getGroups()->count());
        $this->assertEquals(1, $mm4->getGroups()->count());

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $mm1->addGroup($group1);
        $mm2->addGroup($group1);
        $mm3->addGroup($group1);
        $mm4->addGroup($group1);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->persist($mm4);
        $this->dm->flush();

        $this->assertTrue($mm1->containsGroup($group1));
        $this->assertFalse($mm1->containsGroup($group2));
        $this->assertTrue($mm2->containsGroup($group1));
        $this->assertTrue($mm2->containsGroup($group2));
        $this->assertTrue($mm3->containsGroup($group1));
        $this->assertFalse($mm3->containsGroup($group2));
        $this->assertTrue($mm4->containsGroup($group1));
        $this->assertTrue($mm4->containsGroup($group2));
        $this->assertEquals(1, $mm1->getGroups()->count());
        $this->assertEquals(2, $mm2->getGroups()->count());
        $this->assertEquals(1, $mm3->getGroups()->count());
        $this->assertEquals(2, $mm4->getGroups()->count());

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $mm1->removeGroup($group1);
        $mm2->removeGroup($group1);
        $mm3->removeGroup($group1);
        $mm4->removeGroup($group1);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->persist($mm4);
        $this->dm->flush();

        $this->assertFalse($mm1->containsGroup($group1));
        $this->assertFalse($mm1->containsGroup($group2));
        $this->assertFalse($mm2->containsGroup($group1));
        $this->assertTrue($mm2->containsGroup($group2));
        $this->assertFalse($mm3->containsGroup($group1));
        $this->assertFalse($mm3->containsGroup($group2));
        $this->assertFalse($mm4->containsGroup($group1));
        $this->assertTrue($mm4->containsGroup($group2));
        $this->assertEquals(0, $mm1->getGroups()->count());
        $this->assertEquals(1, $mm2->getGroups()->count());
        $this->assertEquals(0, $mm3->getGroups()->count());
        $this->assertEquals(1, $mm4->getGroups()->count());
    }

    public function testMultimediaObjectRemoveGroupService()
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1, false);
        $group1 = $this->groupService->create($group1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2, false);
        $group2 = $this->groupService->create($group2);

        $series = $this->factoryService->createSeries();
        $mm1 = $this->factoryService->createMultimediaObject($series);
        $mm2 = $this->factoryService->createMultimediaObject($series);
        $mm3 = $this->factoryService->createMultimediaObject($series);
        $mm4 = $this->factoryService->createMultimediaObject($series);

        $this->mmService->addGroup($group1, $mm1, false);
        $this->mmService->deleteGroup($group2, $mm1, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->mmService->addGroup($group1, $mm2, false);
        $this->mmService->addGroup($group2, $mm2, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->mmService->addGroup($group1, $mm3, false);
        $this->mmService->deleteGroup($group2, $mm3, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->mmService->addGroup($group1, $mm4, false);
        $this->mmService->addGroup($group2, $mm4, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->assertTrue($mm1->containsGroup($group1));
        $this->assertFalse($mm1->containsGroup($group2));
        $this->assertTrue($mm2->containsGroup($group1));
        $this->assertTrue($mm2->containsGroup($group2));
        $this->assertTrue($mm3->containsGroup($group1));
        $this->assertFalse($mm3->containsGroup($group2));
        $this->assertTrue($mm4->containsGroup($group1));
        $this->assertTrue($mm4->containsGroup($group2));
        $this->assertEquals(1, $mm1->getGroups()->count());
        $this->assertEquals(2, $mm2->getGroups()->count());
        $this->assertEquals(1, $mm3->getGroups()->count());
        $this->assertEquals(2, $mm4->getGroups()->count());

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->mmService->deleteGroup($group1, $mm1, false);
        $this->mmService->deleteGroup($group2, $mm1, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->mmService->deleteGroup($group1, $mm2, false);
        $this->mmService->addGroup($group2, $mm2, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->mmService->deleteGroup($group1, $mm3, false);
        $this->mmService->deleteGroup($group2, $mm3, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->mmService->deleteGroup($group1, $mm4, false);
        $this->mmService->addGroup($group2, $mm4, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->assertFalse($mm1->containsGroup($group1));
        $this->assertFalse($mm1->containsGroup($group2));
        $this->assertFalse($mm2->containsGroup($group1));
        $this->assertTrue($mm2->containsGroup($group2));
        $this->assertFalse($mm3->containsGroup($group1));
        $this->assertFalse($mm3->containsGroup($group2));
        $this->assertFalse($mm4->containsGroup($group1));
        $this->assertTrue($mm4->containsGroup($group2));
        $this->assertEquals(0, $mm1->getGroups()->count());
        $this->assertEquals(1, $mm2->getGroups()->count());
        $this->assertEquals(0, $mm3->getGroups()->count());
        $this->assertEquals(1, $mm4->getGroups()->count());

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->mmService->addGroup($group1, $mm1, false);
        $this->mmService->deleteGroup($group2, $mm1, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->mmService->addGroup($group1, $mm2, false);
        $this->mmService->addGroup($group2, $mm2, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->mmService->addGroup($group1, $mm3, false);
        $this->mmService->deleteGroup($group2, $mm3, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->mmService->addGroup($group1, $mm4, false);
        $this->mmService->addGroup($group2, $mm4, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->assertTrue($mm1->containsGroup($group1));
        $this->assertFalse($mm1->containsGroup($group2));
        $this->assertTrue($mm2->containsGroup($group1));
        $this->assertTrue($mm2->containsGroup($group2));
        $this->assertTrue($mm3->containsGroup($group1));
        $this->assertFalse($mm3->containsGroup($group2));
        $this->assertTrue($mm4->containsGroup($group1));
        $this->assertTrue($mm4->containsGroup($group2));
        $this->assertEquals(1, $mm1->getGroups()->count());
        $this->assertEquals(2, $mm2->getGroups()->count());
        $this->assertEquals(1, $mm3->getGroups()->count());
        $this->assertEquals(2, $mm4->getGroups()->count());

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->mmService->deleteGroup($group1, $mm1, false);
        $this->mmService->deleteGroup($group2, $mm1, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->mmService->deleteGroup($group1, $mm2, false);
        $this->mmService->addGroup($group2, $mm2, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->mmService->deleteGroup($group1, $mm3, false);
        $this->mmService->deleteGroup($group2, $mm3, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->mmService->deleteGroup($group1, $mm4, false);
        $this->mmService->addGroup($group2, $mm4, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());

        $this->assertFalse($mm1->containsGroup($group1));
        $this->assertFalse($mm1->containsGroup($group2));
        // This test should fail using removeElement from ArrayCollection
        // Solved with strategy="setArray"
        $this->assertFalse($mm2->containsGroup($group1));
        //This test should fail using removeElement from ArrayCollection
        // Solved with strategy="setArray"
        $this->assertTrue($mm2->containsGroup($group2));
        $this->assertFalse($mm3->containsGroup($group1));
        $this->assertFalse($mm3->containsGroup($group2));
        // This test should fail using removeElement from ArrayCollection
        // Solved with strategy="setArray"
        $this->assertFalse($mm4->containsGroup($group1));
        // This test should fail using removeElement from ArrayCollection
        // Solved with strategy="setArray"
        $this->assertTrue($mm4->containsGroup($group2));
        $this->assertEquals(0, $mm1->getGroups()->count());
        $this->assertEquals(1, $mm2->getGroups()->count());
        $this->assertEquals(0, $mm3->getGroups()->count());
        $this->assertEquals(1, $mm4->getGroups()->count());
    }

    public function testUserRemoveGroupService()
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1, false);
        $group1 = $this->groupService->create($group1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2, false);
        $group2 = $this->groupService->create($group2);

        $user1 = $this->createUser('user1');
        $user2 = $this->createUser('user2');
        $user3 = $this->createUser('user3');
        $user4 = $this->createUser('user4');

        $user1 = $this->userService->create($user1);
        $user2 = $this->userService->create($user2);
        $user3 = $this->userService->create($user3);
        $user4 = $this->userService->create($user4);

        $this->userService->addGroup($group1, $user1, false);
        $this->userService->deleteGroup($group2, $user1, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->userService->addGroup($group1, $user2, false);
        $this->userService->addGroup($group2, $user2, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->userService->addGroup($group1, $user3, false);
        $this->userService->deleteGroup($group2, $user3, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->userService->addGroup($group1, $user4, false);
        $this->userService->addGroup($group2, $user4, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->assertTrue($user1->containsGroup($group1));
        $this->assertFalse($user1->containsGroup($group2));
        $this->assertTrue($user2->containsGroup($group1));
        $this->assertTrue($user2->containsGroup($group2));
        $this->assertTrue($user3->containsGroup($group1));
        $this->assertFalse($user3->containsGroup($group2));
        $this->assertTrue($user4->containsGroup($group1));
        $this->assertTrue($user4->containsGroup($group2));
        $this->assertEquals(1, $user1->getGroups()->count());
        $this->assertEquals(2, $user2->getGroups()->count());
        $this->assertEquals(1, $user3->getGroups()->count());
        $this->assertEquals(2, $user4->getGroups()->count());

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->userService->deleteGroup($group1, $user1, false);
        $this->userService->deleteGroup($group2, $user1, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->userService->deleteGroup($group1, $user2, false);
        $this->userService->addGroup($group2, $user2, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->userService->deleteGroup($group1, $user3, false);
        $this->userService->deleteGroup($group2, $user3, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->userService->deleteGroup($group1, $user4, false);
        $this->userService->addGroup($group2, $user4, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->assertFalse($user1->containsGroup($group1));
        $this->assertFalse($user1->containsGroup($group2));
        $this->assertFalse($user2->containsGroup($group1));
        $this->assertTrue($user2->containsGroup($group2));
        $this->assertFalse($user3->containsGroup($group1));
        $this->assertFalse($user3->containsGroup($group2));
        $this->assertFalse($user4->containsGroup($group1));
        $this->assertTrue($user4->containsGroup($group2));
        $this->assertEquals(0, $user1->getGroups()->count());
        $this->assertEquals(1, $user2->getGroups()->count());
        $this->assertEquals(0, $user3->getGroups()->count());
        $this->assertEquals(1, $user4->getGroups()->count());

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->userService->addGroup($group1, $user1, false);
        $this->userService->deleteGroup($group2, $user1, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->userService->addGroup($group1, $user2, false);
        $this->userService->addGroup($group2, $user2, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->userService->addGroup($group1, $user3, false);
        $this->userService->deleteGroup($group2, $user3, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->userService->addGroup($group1, $user4, false);
        $this->userService->addGroup($group2, $user4, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->assertTrue($user1->containsGroup($group1));
        $this->assertFalse($user1->containsGroup($group2));
        $this->assertTrue($user2->containsGroup($group1));
        $this->assertTrue($user2->containsGroup($group2));
        $this->assertTrue($user3->containsGroup($group1));
        $this->assertFalse($user3->containsGroup($group2));
        $this->assertTrue($user4->containsGroup($group1));
        $this->assertTrue($user4->containsGroup($group2));
        $this->assertEquals(1, $user1->getGroups()->count());
        $this->assertEquals(2, $user2->getGroups()->count());
        $this->assertEquals(1, $user3->getGroups()->count());
        $this->assertEquals(2, $user4->getGroups()->count());

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->userService->deleteGroup($group1, $user1, false);
        $this->userService->deleteGroup($group2, $user1, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->userService->deleteGroup($group1, $user2, false);
        $this->userService->addGroup($group2, $user2, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->userService->deleteGroup($group1, $user3, false);
        $this->userService->deleteGroup($group2, $user3, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->userService->deleteGroup($group1, $user4, false);
        $this->userService->addGroup($group2, $user4, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $user1 = $this->userRepo->find($user1->getId());
        $user2 = $this->userRepo->find($user2->getId());
        $user3 = $this->userRepo->find($user3->getId());
        $user4 = $this->userRepo->find($user4->getId());

        $this->assertFalse($user1->containsGroup($group1));
        $this->assertFalse($user1->containsGroup($group2));
        // This test should fail using removeElement from ArrayCollection
        // Solved with strategy="setArray"
        $this->assertFalse($user2->containsGroup($group1));
        //This test should fail using removeElement from ArrayCollection
        // Solved with strategy="setArray"
        $this->assertTrue($user2->containsGroup($group2));
        $this->assertFalse($user3->containsGroup($group1));
        $this->assertFalse($user3->containsGroup($group2));
        // This test should fail using removeElement from ArrayCollection
        // Solved with strategy="setArray"
        $this->assertFalse($user4->containsGroup($group1));
        // This test should fail using removeElement from ArrayCollection
        // Solved with strategy="setArray"
        $this->assertTrue($user4->containsGroup($group2));
        $this->assertEquals(0, $user1->getGroups()->count());
        $this->assertEquals(1, $user2->getGroups()->count());
        $this->assertEquals(0, $user3->getGroups()->count());
        $this->assertEquals(1, $user4->getGroups()->count());
    }

    public function testEmbeddedBroadcastRemoveGroupService()
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1, false);
        $group1 = $this->groupService->create($group1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2, false);
        $group2 = $this->groupService->create($group2);

        $series = $this->factoryService->createSeries();
        $mm1 = $this->factoryService->createMultimediaObject($series);
        $mm2 = $this->factoryService->createMultimediaObject($series);
        $mm3 = $this->factoryService->createMultimediaObject($series);
        $mm4 = $this->factoryService->createMultimediaObject($series);

        $mm1 = $this->ebService->setByType($mm1, EmbeddedBroadcast::TYPE_GROUPS);
        $mm2 = $this->ebService->setByType($mm2, EmbeddedBroadcast::TYPE_GROUPS);
        $mm3 = $this->ebService->setByType($mm3, EmbeddedBroadcast::TYPE_GROUPS);
        $mm4 = $this->ebService->setByType($mm4, EmbeddedBroadcast::TYPE_GROUPS);

        $this->ebService->addGroup($group1, $mm1, false);
        $this->ebService->deleteGroup($group2, $mm1, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->ebService->addGroup($group1, $mm2, false);
        $this->ebService->addGroup($group2, $mm2, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->ebService->addGroup($group1, $mm3, false);
        $this->ebService->deleteGroup($group2, $mm3, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->ebService->addGroup($group1, $mm4, false);
        $this->ebService->addGroup($group2, $mm4, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->assertTrue($eb1->containsGroup($group1));
        $this->assertFalse($eb1->containsGroup($group2));
        $this->assertTrue($eb2->containsGroup($group1));
        $this->assertTrue($eb2->containsGroup($group2));
        $this->assertTrue($eb3->containsGroup($group1));
        $this->assertFalse($eb3->containsGroup($group2));
        $this->assertTrue($eb4->containsGroup($group1));
        $this->assertTrue($eb4->containsGroup($group2));
        $this->assertEquals(1, $eb1->getGroups()->count());
        $this->assertEquals(2, $eb2->getGroups()->count());
        $this->assertEquals(1, $eb3->getGroups()->count());
        $this->assertEquals(2, $eb4->getGroups()->count());

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->ebService->deleteGroup($group1, $mm1, false);
        $this->ebService->deleteGroup($group2, $mm1, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->ebService->deleteGroup($group1, $mm2, false);
        $this->ebService->addGroup($group2, $mm2, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->ebService->deleteGroup($group1, $mm3, false);
        $this->ebService->deleteGroup($group2, $mm3, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->ebService->deleteGroup($group1, $mm4, false);
        $this->ebService->addGroup($group2, $mm4, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->assertFalse($eb1->containsGroup($group1));
        $this->assertFalse($eb1->containsGroup($group2));
        $this->assertFalse($eb2->containsGroup($group1));
        $this->assertTrue($eb2->containsGroup($group2));
        $this->assertFalse($eb3->containsGroup($group1));
        $this->assertFalse($eb3->containsGroup($group2));
        $this->assertFalse($eb4->containsGroup($group1));
        $this->assertTrue($eb4->containsGroup($group2));
        $this->assertEquals(0, $eb1->getGroups()->count());
        $this->assertEquals(1, $eb2->getGroups()->count());
        $this->assertEquals(0, $eb3->getGroups()->count());
        $this->assertEquals(1, $eb4->getGroups()->count());

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->ebService->addGroup($group1, $mm1, false);
        $this->ebService->deleteGroup($group2, $mm1, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->ebService->addGroup($group1, $mm2, false);
        $this->ebService->addGroup($group2, $mm2, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->ebService->addGroup($group1, $mm3, false);
        $this->ebService->deleteGroup($group2, $mm3, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->ebService->addGroup($group1, $mm4, false);
        $this->ebService->addGroup($group2, $mm4, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->assertTrue($eb1->containsGroup($group1));
        $this->assertFalse($eb1->containsGroup($group2));
        $this->assertTrue($eb2->containsGroup($group1));
        $this->assertTrue($eb2->containsGroup($group2));
        $this->assertTrue($eb3->containsGroup($group1));
        $this->assertFalse($eb3->containsGroup($group2));
        $this->assertTrue($eb4->containsGroup($group1));
        $this->assertTrue($eb4->containsGroup($group2));
        $this->assertEquals(1, $eb1->getGroups()->count());
        $this->assertEquals(2, $eb2->getGroups()->count());
        $this->assertEquals(1, $eb3->getGroups()->count());
        $this->assertEquals(2, $eb4->getGroups()->count());

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->ebService->deleteGroup($group1, $mm1, false);
        $this->ebService->deleteGroup($group2, $mm1, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->ebService->deleteGroup($group1, $mm2, false);
        $this->ebService->addGroup($group2, $mm2, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->ebService->deleteGroup($group1, $mm3, false);
        $this->ebService->deleteGroup($group2, $mm3, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->ebService->deleteGroup($group1, $mm4, false);
        $this->ebService->addGroup($group2, $mm4, false);
        $this->dm->flush();

        $this->dm->clear();

        $group1 = $this->groupRepo->find($group1->getId());
        $group2 = $this->groupRepo->find($group2->getId());
        $mm1 = $this->mmRepo->find($mm1->getId());
        $mm2 = $this->mmRepo->find($mm2->getId());
        $mm3 = $this->mmRepo->find($mm3->getId());
        $mm4 = $this->mmRepo->find($mm4->getId());
        $eb1 = $mm1->getEmbeddedBroadcast();
        $eb2 = $mm2->getEmbeddedBroadcast();
        $eb3 = $mm3->getEmbeddedBroadcast();
        $eb4 = $mm4->getEmbeddedBroadcast();

        $this->assertFalse($eb1->containsGroup($group1));
        $this->assertFalse($eb1->containsGroup($group2));
        // This test should fail using removeElement from ArrayCollection
        // Solved with strategy="setArray"
        $this->assertFalse($eb2->containsGroup($group1));
        //This test should fail using removeElement from ArrayCollection
        // Solved with strategy="setArray"
        $this->assertTrue($eb2->containsGroup($group2));
        $this->assertFalse($eb3->containsGroup($group1));
        $this->assertFalse($eb3->containsGroup($group2));
        // This test should fail using removeElement from ArrayCollection
        // Solved with strategy="setArray"
        $this->assertFalse($eb4->containsGroup($group1));
        // This test should fail using removeElement from ArrayCollection
        // Solved with strategy="setArray"
        $this->assertTrue($eb4->containsGroup($group2));
        $this->assertEquals(0, $eb1->getGroups()->count());
        $this->assertEquals(1, $eb2->getGroups()->count());
        $this->assertEquals(0, $eb3->getGroups()->count());
        $this->assertEquals(1, $eb4->getGroups()->count());
    }

    private function createGroup($key = 'Group1', $name = 'Group 1', $persist = true)
    {
        $group = new Group();

        $group->setKey($key);
        $group->setName($name);

        if ($persist) {
            $this->dm->persist($group);
            $this->dm->flush();
        }

        return $group;
    }

    private function createUser($username = 'user')
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username.'@mail.com');

        return $user;
    }
}
