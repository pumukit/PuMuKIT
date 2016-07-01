<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\User;
use Doctrine\Common\Collections\ArrayCollection;

class RemoveElementTest extends WebTestCase
{
    private $dm;
    private $mmRepo;
    private $groupRepo;
    private $factoryService;
    private $mmService;
    private $groupService;

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->mmRepo = $this->dm
            ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->groupRepo = $this->dm
            ->getRepository('PumukitSchemaBundle:Group');
        $this->factoryService = static::$kernel->getContainer()
            ->get('pumukitschema.factory');
        $this->mmService = static::$kernel->getContainer()
            ->get('pumukitschema.multimedia_object');
        $this->groupService = static::$kernel->getContainer()
            ->get('pumukitschema.group');

        //DELETE DATABASE
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Group')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:User')
            ->remove(array());
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
        $series = $this->factoryService->createSeries();
        $mm1 = $this->factoryService->createMultimediaObject($series);
        $mm2 = $this->factoryService->createMultimediaObject($series);
        $mm3 = $this->factoryService->createMultimediaObject($series);
        $mm4 = $this->factoryService->createMultimediaObject($series);

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
        $this->mmService->addGroup($group1, $mm2, false);
        $this->mmService->addGroup($group2, $mm2, false);
        $this->dm->flush();
        $this->mmService->addGroup($group1, $mm3, false);
        $this->mmService->deleteGroup($group2, $mm3, false);
        $this->dm->flush();
        $this->mmService->addGroup($group1, $mm4, false);
        $this->mmService->addGroup($group2, $mm4, false);
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

        $this->mmService->deleteGroup($group1, $mm1, false);
        $this->mmService->deleteGroup($group2, $mm1, false);
        $this->dm->flush();
        $this->mmService->deleteGroup($group1, $mm2, false);
        $this->mmService->addGroup($group2, $mm2, false);
        $this->dm->flush();
        $this->mmService->deleteGroup($group1, $mm3, false);
        $this->mmService->deleteGroup($group2, $mm3, false);
        $this->dm->flush();
        $this->mmService->deleteGroup($group1, $mm4, false);
        $this->mmService->addGroup($group2, $mm4, false);
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

        $this->mmService->addGroup($group1, $mm1, false);
        $this->dm->flush();
        $this->mmService->addGroup($group1, $mm2, false);
        $this->dm->flush();
        $this->mmService->addGroup($group1, $mm3, false);
        $this->dm->flush();
        $this->mmService->addGroup($group1, $mm4, false);
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

        $this->mmService->deleteGroup($group1, $mm1, false);
        $this->dm->flush();
        $this->mmService->deleteGroup($group1, $mm2, false);
        $this->dm->flush();
        $this->mmService->deleteGroup($group1, $mm3, false);
        $this->dm->flush();
        $this->mmService->deleteGroup($group1, $mm4, false);
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

    private function createGroup($key='Group1', $name='Group 1', $persist = true)
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
}