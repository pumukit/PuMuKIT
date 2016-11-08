<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;

class TagServiceTest extends WebTestCase
{
    private $dm;
    private $tagRepo;
    private $mmobjRepo;
    private $tagService;

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
        $this->tagRepo = $this->dm
          ->getRepository('PumukitSchemaBundle:Tag');
        $this->mmobjRepo = $this->dm
          ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->tagService = static::$kernel->getContainer()->get('pumukitschema.tag');

        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')
          ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Tag')
          ->remove(array());
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->tagRepo = null;
        $this->mmobjRepo = null;
        $this->tagService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testAddTagToMultimediaObject()
    {
        $mmobj = $this->createMultimediaObject('titulo cualquiera');
        $tag = $this->createTagWithTree('tag1');

        $this->assertEquals(0, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals($mmobj, $this->mmobjRepo->find($mmobj->getId()));
        $this->assertEquals($tag, $this->tagRepo->find($tag->getId()));
        $this->assertEquals(0, $tag->getNumberMultimediaObjects());

        $addedTags = $this->tagService->addTagToMultimediaObject($mmobj, $tag->getId());
        $this->assertEquals(3, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals(3, count($addedTags));
        $this->assertTrue($this->mmobjRepo->find($mmobj->getId())->containsTag($tag));
        $this->assertEquals(0, count($this->tagService->addTagToMultimediaObject($mmobj, $tag->getId())));
        $this->assertEquals(1, $tag->getNumberMultimediaObjects());
    }

    /**
     * @expectedException         Exception
     * @expectedExceptionMessage  not found
     */
    public function testTagFindNotExistinAdd()
    {
        $mmobj = $this->createMultimediaObject('titulo cualquiera');
        $tag = null;

        $this->tagService->addTagToMultimediaObject($mmobj, $tag);
        $this->tagService->removeTagFromMultimediaObject($mmobj, $tag);
    }

    public function testAddTagWithoutRoot()
    {
        $mmobj = $this->createMultimediaObject('titulo cualquiera');
        $tag = $this->createTagWithTree('tag1', false);

        $this->tagService->addTagToMultimediaObject($mmobj, $tag->getId());
        $this->assertEquals(3, count($this->mmobjRepo->find($mmobj->getId())->getTags()));

        $this->tagService->removeTagFromMultimediaObject($mmobj, $tag->getId());
        $this->assertEquals(0, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
    }

    public function testRemoveTagFromMultimediaObject()
    {
        $mmobj = $this->createMultimediaObject('multimedia object test');
        $tag = $this->createTagWithTree('tag1');
        $broTag = $this->tagRepo->findOneByCod('brother');
        $parentTag = $this->tagRepo->findOneByCod('parent');

        $this->assertEquals(0, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals(0, $this->tagRepo->findOneByCod('tag1')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('parent')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('brother')->getNumberMultimediaObjects());

        $addedTags = $this->tagService->addTagToMultimediaObject($mmobj, $tag->getId());
        $this->assertEquals(3, count($addedTags));
        $this->assertEquals(3, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals(1, $this->tagRepo->findOneByCod('tag1')->getNumberMultimediaObjects());
        $this->assertEquals(1, $this->tagRepo->findOneByCod('parent')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('brother')->getNumberMultimediaObjects());

        $addedTags = $this->tagService->addTagToMultimediaObject($mmobj, $broTag->getId());
        $this->assertEquals(1, count($addedTags));
        $this->assertEquals(4, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals(1, $this->tagRepo->findOneByCod('tag1')->getNumberMultimediaObjects());
        $this->assertEquals(1, $this->tagRepo->findOneByCod('parent')->getNumberMultimediaObjects());
        $this->assertEquals(1, $this->tagRepo->findOneByCod('brother')->getNumberMultimediaObjects());

        $removedTags = $this->tagService->removeTagFromMultimediaObject($mmobj, $tag->getId());
        $this->assertEquals(1, count($removedTags));
        $this->assertEquals(3, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals(0, $this->tagRepo->findOneByCod('tag1')->getNumberMultimediaObjects());
        $this->assertEquals(1, $this->tagRepo->findOneByCod('parent')->getNumberMultimediaObjects());
        $this->assertEquals(1, $this->tagRepo->findOneByCod('brother')->getNumberMultimediaObjects());

        $removedTags = $this->tagService->removeTagFromMultimediaObject($mmobj, $parentTag->getId());
        $this->assertEquals(0, count($removedTags));
        $this->assertEquals(3, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals(0, $this->tagRepo->findOneByCod('tag1')->getNumberMultimediaObjects());
        $this->assertEquals(1, $this->tagRepo->findOneByCod('parent')->getNumberMultimediaObjects());
        $this->assertEquals(1, $this->tagRepo->findOneByCod('brother')->getNumberMultimediaObjects());

        $removedTags = $this->tagService->removeTagFromMultimediaObject($mmobj, $broTag->getId());
        $this->assertEquals(3, count($removedTags));
        $this->assertEquals(0, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals(0, $this->tagRepo->findOneByCod('tag1')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('parent')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('brother')->getNumberMultimediaObjects());

        $addedTags = $this->tagService->addTagToMultimediaObject($mmobj, $parentTag->getId());
        $this->assertEquals(2, count($addedTags));
        $this->assertEquals(2, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals(0, $this->tagRepo->findOneByCod('tag1')->getNumberMultimediaObjects());
        $this->assertEquals(1, $this->tagRepo->findOneByCod('parent')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('brother')->getNumberMultimediaObjects());

        $removedTags = $this->tagService->removeTagFromMultimediaObject($mmobj, $parentTag->getId());
        $this->assertEquals(2, count($removedTags));
        $this->assertEquals(0, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals(0, $this->tagRepo->findOneByCod('tag1')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('parent')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('brother')->getNumberMultimediaObjects());
    }

    public function testAddAndRemoveTagToPrototype()
    {
        $mmobj = $this->createMultimediaObject('multimedia object test', true);
        $tag = $this->createTagWithTree('tag1');
        $broTag = $this->tagRepo->findOneByCod('brother');
        $parentTag = $this->tagRepo->findOneByCod('parent');

        $this->assertEquals(0, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals(0, $this->tagRepo->findOneByCod('tag1')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('parent')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('brother')->getNumberMultimediaObjects());

        $addedTags = $this->tagService->addTagToMultimediaObject($mmobj, $tag->getId());
        $this->assertEquals(3, count($addedTags));
        $this->assertEquals(3, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals(0, $this->tagRepo->findOneByCod('tag1')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('parent')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('brother')->getNumberMultimediaObjects());

        $addedTags = $this->tagService->addTagToMultimediaObject($mmobj, $broTag->getId());
        $this->assertEquals(1, count($addedTags));
        $this->assertEquals(4, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals(0, $this->tagRepo->findOneByCod('tag1')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('parent')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('brother')->getNumberMultimediaObjects());

        $removedTags = $this->tagService->removeTagFromMultimediaObject($mmobj, $tag->getId());
        $this->assertEquals(1, count($removedTags));
        $this->assertEquals(3, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals(0, $this->tagRepo->findOneByCod('tag1')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('parent')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('brother')->getNumberMultimediaObjects());

        $removedTags = $this->tagService->removeTagFromMultimediaObject($mmobj, $parentTag->getId());
        $this->assertEquals(0, count($removedTags));
        $this->assertEquals(3, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals(0, $this->tagRepo->findOneByCod('tag1')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('parent')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('brother')->getNumberMultimediaObjects());

        $removedTags = $this->tagService->removeTagFromMultimediaObject($mmobj, $broTag->getId());
        $this->assertEquals(3, count($removedTags));
        $this->assertEquals(0, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals(0, $this->tagRepo->findOneByCod('tag1')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('parent')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('brother')->getNumberMultimediaObjects());

        $addedTags = $this->tagService->addTagToMultimediaObject($mmobj, $parentTag->getId());
        $this->assertEquals(2, count($addedTags));
        $this->assertEquals(2, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals(0, $this->tagRepo->findOneByCod('tag1')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('parent')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('brother')->getNumberMultimediaObjects());

        $removedTags = $this->tagService->removeTagFromMultimediaObject($mmobj, $parentTag->getId());
        $this->assertEquals(2, count($removedTags));
        $this->assertEquals(0, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals(0, $this->tagRepo->findOneByCod('tag1')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('parent')->getNumberMultimediaObjects());
        $this->assertEquals(0, $this->tagRepo->findOneByCod('brother')->getNumberMultimediaObjects());
    }

    public function testResetTags()
    {
        $mmobj1 = $this->createMultimediaObject('mmobj1');
        $mmobj2 = $this->createMultimediaObject('mmobj2');
        $mmobj3 = $this->createMultimediaObject('mmobj3');
        $tag1 = $this->createTagWithTree('tag1', true);
        $tag2 = $this->createTagWithTree('tag2', false);
        $tag3 = $this->createTagWithTree('tag3', false);

        $this->tagService->addTagToMultimediaObject($mmobj1, $tag1->getId());
        $this->tagService->addTagToMultimediaObject($mmobj1, $tag2->getId());
        $this->tagService->addTagToMultimediaObject($mmobj1, $tag3->getId());
        $this->tagService->addTagToMultimediaObject($mmobj2, $tag2->getId());

        $this->assertEquals(5, count($this->mmobjRepo->find($mmobj1->getId())->getTags()));
        $this->assertEquals(3, count($this->mmobjRepo->find($mmobj2->getId())->getTags()));
        $this->assertEquals(0, count($this->mmobjRepo->find($mmobj3->getId())->getTags()));
        $this->assertEquals(1, $tag1->getNumberMultimediaObjects());
        $this->assertEquals(2, $tag2->getNumberMultimediaObjects());
        $this->assertEquals(1, $tag3->getNumberMultimediaObjects());

        $this->tagService->resetTags(array($mmobj1, $mmobj2, $mmobj3), $mmobj1->getTags()->toArray());

        $this->assertEquals(5, count($this->mmobjRepo->find($mmobj1->getId())->getTags()));
        $this->assertEquals(5, count($this->mmobjRepo->find($mmobj2->getId())->getTags()));
        $this->assertEquals(5, count($this->mmobjRepo->find($mmobj3->getId())->getTags()));
        $this->assertEquals(3, $tag1->getNumberMultimediaObjects());
        $this->assertEquals(3, $tag2->getNumberMultimediaObjects());
        $this->assertEquals(3, $tag3->getNumberMultimediaObjects());

        $this->tagService->resetTags(array($mmobj1, $mmobj2, $mmobj3), array());

        $this->assertEquals(0, count($this->mmobjRepo->find($mmobj1->getId())->getTags()));
        $this->assertEquals(0, count($this->mmobjRepo->find($mmobj2->getId())->getTags()));
        $this->assertEquals(0, count($this->mmobjRepo->find($mmobj3->getId())->getTags()));

        $this->assertEquals(0, $tag1->getNumberMultimediaObjects());
        $this->assertEquals(0, $tag2->getNumberMultimediaObjects());
        $this->assertEquals(0, $tag3->getNumberMultimediaObjects());
    }

    /**
     * @expectedException         Exception
     * @expectedExceptionMessage  not found
     */
    public function testTagFindNotExistInRemove()
    {
        $mmobj = $this->createMultimediaObject('titulo cualquiera');
        $tag = null;

        $this->tagService->removeTagFromMultimediaObject($mmobj, $tag);
    }

    public function testResetTagsWithPrototypes()
    {
        $mmobj1 = $this->createMultimediaObject('mmobj1', true);
        $mmobj2 = $this->createMultimediaObject('mmobj2');
        $mmobj3 = $this->createMultimediaObject('mmobj3');
        $tag1 = $this->createTagWithTree('tag1', true);
        $tag2 = $this->createTagWithTree('tag2', false);
        $tag3 = $this->createTagWithTree('tag3', false);

        $this->tagService->addTagToMultimediaObject($mmobj1, $tag1->getId());
        $this->tagService->addTagToMultimediaObject($mmobj1, $tag2->getId());
        $this->tagService->addTagToMultimediaObject($mmobj1, $tag3->getId());
        $this->tagService->addTagToMultimediaObject($mmobj2, $tag2->getId());

        $this->assertEquals(5, count($this->mmobjRepo->find($mmobj1->getId())->getTags()));
        $this->assertEquals(3, count($this->mmobjRepo->find($mmobj2->getId())->getTags()));
        $this->assertEquals(0, count($this->mmobjRepo->find($mmobj3->getId())->getTags()));
        $this->assertEquals(0, $tag1->getNumberMultimediaObjects());
        $this->assertEquals(1, $tag2->getNumberMultimediaObjects());
        $this->assertEquals(0, $tag3->getNumberMultimediaObjects());

        $this->tagService->resetTags(array($mmobj1, $mmobj2, $mmobj3), $mmobj1->getTags()->toArray());

        $this->assertEquals(5, count($this->mmobjRepo->find($mmobj1->getId())->getTags()));
        $this->assertEquals(5, count($this->mmobjRepo->find($mmobj2->getId())->getTags()));
        $this->assertEquals(5, count($this->mmobjRepo->find($mmobj3->getId())->getTags()));
        $this->assertEquals(2, $tag1->getNumberMultimediaObjects());
        $this->assertEquals(2, $tag2->getNumberMultimediaObjects());
        $this->assertEquals(2, $tag3->getNumberMultimediaObjects());

        $this->tagService->resetTags(array($mmobj1, $mmobj2, $mmobj3), array());

        $this->assertEquals(0, count($this->mmobjRepo->find($mmobj1->getId())->getTags()));
        $this->assertEquals(0, count($this->mmobjRepo->find($mmobj2->getId())->getTags()));
        $this->assertEquals(0, count($this->mmobjRepo->find($mmobj3->getId())->getTags()));

        $this->assertEquals(0, $tag1->getNumberMultimediaObjects());
        $this->assertEquals(0, $tag2->getNumberMultimediaObjects());
        $this->assertEquals(0, $tag3->getNumberMultimediaObjects());
    }

    public function testSaveTag()
    {
        $cod = 'tag1';
        $firstTitle = ucfirst($cod);
        $tag = $this->createTagWithTree($cod);

        $lastUpdatedDate = $tag->getUpdated();

        $newTitle = 'Tag number 1';
        $tag->setTitle($newTitle);

        $tag = $this->tagService->saveTag($tag);

        $updatedTag = $this->tagRepo->find($tag->getId());
        $this->assertEquals($newTitle, $updatedTag->getTitle());
        $this->assertNotEquals($lastUpdatedDate, $updatedTag->getUpdated());
    }

    public function testUpdateTag()
    {
        $cod = 'tag1';
        $firstTitle = ucfirst($cod);
        $tag = $this->createTagWithTree($cod);

        $lastUpdatedDate = $tag->getUpdated();

        $multimediaObject = $this->createMultimediaObject('multimedia object 1');
        $addedTags = $this->tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());

        $multimediaObject = $this->mmobjRepo->find($multimediaObject->getId());
        $tag = $this->tagRepo->find($tag->getId());

        $embeddedTags = $multimediaObject->getTags();
        foreach ($embeddedTags as $embeddedTag) {
            if ($embeddedTag->getId() === $tag->getId()) {
                $this->assertEquals($firstTitle, $embeddedTag->getTitle());
            }
        }

        $lastUpdatedDate = $tag->getUpdated();

        $newTitle = 'Tag number 1';
        $tag->setTitle($newTitle);

        $tag = $this->tagService->updateTag($tag);

        $multimediaObject = $this->mmobjRepo->find($multimediaObject->getId());
        $tag = $this->tagRepo->find($tag->getId());

        $embeddedTags = $multimediaObject->getTags();
        foreach ($embeddedTags as $embeddedTag) {
            if ($embeddedTag->getId() === $tag->getId()) {
                $this->assertEquals($newTitle, $embeddedTag->getTitle());
                $this->assertNotEquals($lastUpdatedDate, $embeddedTag->getUpdated());
                $this->assertNotEquals($lastUpdatedDate, $tag->getUpdated());
            }
        }
    }

    private function createMultimediaObject($title, $prototype = false)
    {
        $locale = 'en';
        $status = $prototype ? MultimediaObject::STATUS_PROTOTYPE : MultimediaObject::STATUS_NEW;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $subtitle = 'Subtitle paragraph';
        $description = 'Description text';
        $duration = 300;

        $mmobj = new MultimediaObject();
        $mmobj->setLocale($locale);
        $mmobj->setStatus($status);
        $mmobj->setRecordDate($record_date);
        $mmobj->setPublicDate($public_date);
        $mmobj->setTitle($title);
        $mmobj->setSubtitle($subtitle);
        $mmobj->setDescription($description);
        $mmobj->setDuration($duration);

        $this->dm->persist($mmobj);
        $this->dm->flush();

        return $mmobj;
    }

    private function createTagWithTree($cod, $withROOT = true)
    {
        if ($withROOT) {
            $rootTag = $this->tagRepo->findOneByCod('ROOT');
            if (null == $rootTag) {
                $rootTag = new Tag();
                $rootTag->setCod('ROOT');
                $this->dm->persist($rootTag);
            }
        } else {
            $rootTag = $this->tagRepo->findOneByCod('grandparent');
            if (null == $rootTag) {
                $rootTag = new Tag();
                $rootTag->setCod('grandparent');
                $this->dm->persist($rootTag);
            }
        }

        $locale = 'en';

        $parentTag = $this->tagRepo->findOneByCod('parent');
        if (null == $parentTag) {
            $parentTag = new Tag();
            $parentTag->setLocale($locale);
            $parentTag->setCod('parent');
            $parentTag->setTitle('Parent');
            $parentTag->setParent($rootTag);
            $this->dm->persist($parentTag);
        }

        $tag = new Tag();
        $tag->setLocale($locale);
        $tag->setCod($cod);
        $tag->setTitle(ucfirst($cod));
        $tag->setParent($parentTag);
        $this->dm->persist($tag);

        $broTag = $this->tagRepo->findOneByCod('brother');
        if (null == $broTag) {
            $broTag = new Tag();
            $broTag->setLocale($locale);
            $broTag->setCod('brother');
            $broTag->setTitle('Brother');
            $broTag->setParent($parentTag);
            $this->dm->persist($broTag);
        }

        $this->dm->flush();

        return $tag;
    }
}
