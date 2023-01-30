<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;

/**
 * @internal
 *
 * @coversNothing
 */
class TagServiceTest extends PumukitTestCase
{
    private $tagRepo;
    private $mmobjRepo;
    private $tagService;
    private $factoryService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        parent::setUp();

        $this->tagRepo = $this->dm->getRepository(Tag::class);
        $this->mmobjRepo = $this->dm->getRepository(MultimediaObject::class);
        $this->tagService = static::$kernel->getContainer()->get('pumukitschema.tag');
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->tagRepo = null;
        $this->mmobjRepo = null;
        $this->tagService = null;
        gc_collect_cycles();
    }

    public function testAddTagToMultimediaObject()
    {
        $mmobj = $this->createMultimediaObject('titulo cualquiera');
        $tag = $this->createTagWithTree('tag1');

        static::assertCount(0, $this->mmobjRepo->find($mmobj->getId())->getTags());
        static::assertEquals($mmobj, $this->mmobjRepo->find($mmobj->getId()));
        static::assertEquals($tag, $this->tagRepo->find($tag->getId()));
        static::assertEquals(0, $tag->getNumberMultimediaObjects());

        $addedTags = $this->tagService->addTagToMultimediaObject($mmobj, $tag->getId());
        static::assertCount(3, $this->mmobjRepo->find($mmobj->getId())->getTags());
        static::assertCount(3, $addedTags);
        static::assertTrue($this->mmobjRepo->find($mmobj->getId())->containsTag($tag));
        static::assertCount(0, $this->tagService->addTagToMultimediaObject($mmobj, $tag->getId()));
        static::assertEquals(1, $tag->getNumberMultimediaObjects());
    }

    public function testTagFindNotExistinAdd()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not found');
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
        static::assertCount(3, $this->mmobjRepo->find($mmobj->getId())->getTags());

        $this->tagService->removeTagFromMultimediaObject($mmobj, $tag->getId());
        static::assertCount(0, $this->mmobjRepo->find($mmobj->getId())->getTags());
    }

    public function testRemoveTagFromMultimediaObject()
    {
        $mmobj = $this->createMultimediaObject('multimedia object test');
        $tag = $this->createTagWithTree('tag1');
        $broTag = $this->tagRepo->findOneBy(['cod' => 'brother']);
        $parentTag = $this->tagRepo->findOneBy(['cod' => 'parent']);

        static::assertCount(0, $this->mmobjRepo->find($mmobj->getId())->getTags());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'tag1'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'parent'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'brother'])->getNumberMultimediaObjects());

        $addedTags = $this->tagService->addTagToMultimediaObject($mmobj, $tag->getId());
        static::assertCount(3, $addedTags);
        static::assertCount(3, $this->mmobjRepo->find($mmobj->getId())->getTags());
        static::assertEquals(1, $this->tagRepo->findOneBy(['cod' => 'tag1'])->getNumberMultimediaObjects());
        static::assertEquals(1, $this->tagRepo->findOneBy(['cod' => 'parent'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'brother'])->getNumberMultimediaObjects());

        $addedTags = $this->tagService->addTagToMultimediaObject($mmobj, $broTag->getId());
        static::assertCount(1, $addedTags);
        static::assertCount(4, $this->mmobjRepo->find($mmobj->getId())->getTags());
        static::assertEquals(1, $this->tagRepo->findOneBy(['cod' => 'tag1'])->getNumberMultimediaObjects());
        static::assertEquals(1, $this->tagRepo->findOneBy(['cod' => 'parent'])->getNumberMultimediaObjects());
        static::assertEquals(1, $this->tagRepo->findOneBy(['cod' => 'brother'])->getNumberMultimediaObjects());

        $removedTags = $this->tagService->removeTagFromMultimediaObject($mmobj, $tag->getId());
        static::assertCount(1, $removedTags);
        static::assertCount(3, $this->mmobjRepo->find($mmobj->getId())->getTags());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'tag1'])->getNumberMultimediaObjects());
        static::assertEquals(1, $this->tagRepo->findOneBy(['cod' => 'parent'])->getNumberMultimediaObjects());
        static::assertEquals(1, $this->tagRepo->findOneBy(['cod' => 'brother'])->getNumberMultimediaObjects());

        $removedTags = $this->tagService->removeTagFromMultimediaObject($mmobj, $parentTag->getId());
        static::assertCount(0, $removedTags);
        static::assertCount(3, $this->mmobjRepo->find($mmobj->getId())->getTags());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'tag1'])->getNumberMultimediaObjects());
        static::assertEquals(1, $this->tagRepo->findOneBy(['cod' => 'parent'])->getNumberMultimediaObjects());
        static::assertEquals(1, $this->tagRepo->findOneBy(['cod' => 'brother'])->getNumberMultimediaObjects());

        $removedTags = $this->tagService->removeTagFromMultimediaObject($mmobj, $broTag->getId());
        static::assertCount(3, $removedTags);
        static::assertCount(0, $this->mmobjRepo->find($mmobj->getId())->getTags());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'tag1'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'parent'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'brother'])->getNumberMultimediaObjects());

        $addedTags = $this->tagService->addTagToMultimediaObject($mmobj, $parentTag->getId());
        static::assertCount(2, $addedTags);
        static::assertCount(2, $this->mmobjRepo->find($mmobj->getId())->getTags());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'tag1'])->getNumberMultimediaObjects());
        static::assertEquals(1, $this->tagRepo->findOneBy(['cod' => 'parent'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'brother'])->getNumberMultimediaObjects());

        $removedTags = $this->tagService->removeTagFromMultimediaObject($mmobj, $parentTag->getId());
        static::assertCount(2, $removedTags);
        static::assertCount(0, $this->mmobjRepo->find($mmobj->getId())->getTags());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'tag1'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'parent'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'brother'])->getNumberMultimediaObjects());
    }

    public function testAddAndRemoveTagToPrototype()
    {
        $mmobj = $this->createMultimediaObject('multimedia object test', true);
        $tag = $this->createTagWithTree('tag1');
        $broTag = $this->tagRepo->findOneBy(['cod' => 'brother']);
        $parentTag = $this->tagRepo->findOneBy(['cod' => 'parent']);

        static::assertCount(0, $this->mmobjRepo->find($mmobj->getId())->getTags());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'tag1'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'parent'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'brother'])->getNumberMultimediaObjects());

        $addedTags = $this->tagService->addTagToMultimediaObject($mmobj, $tag->getId());
        static::assertCount(3, $addedTags);
        static::assertCount(3, $this->mmobjRepo->find($mmobj->getId())->getTags());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'tag1'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'parent'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'brother'])->getNumberMultimediaObjects());

        $addedTags = $this->tagService->addTagToMultimediaObject($mmobj, $broTag->getId());
        static::assertCount(1, $addedTags);
        static::assertCount(4, $this->mmobjRepo->find($mmobj->getId())->getTags());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'tag1'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'parent'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'brother'])->getNumberMultimediaObjects());

        $removedTags = $this->tagService->removeTagFromMultimediaObject($mmobj, $tag->getId());
        static::assertCount(1, $removedTags);
        static::assertCount(3, $this->mmobjRepo->find($mmobj->getId())->getTags());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'tag1'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'parent'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'brother'])->getNumberMultimediaObjects());

        $removedTags = $this->tagService->removeTagFromMultimediaObject($mmobj, $parentTag->getId());
        static::assertCount(0, $removedTags);
        static::assertCount(3, $this->mmobjRepo->find($mmobj->getId())->getTags());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'tag1'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'parent'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'brother'])->getNumberMultimediaObjects());

        $removedTags = $this->tagService->removeTagFromMultimediaObject($mmobj, $broTag->getId());
        static::assertCount(3, $removedTags);
        static::assertCount(0, $this->mmobjRepo->find($mmobj->getId())->getTags());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'tag1'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'parent'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'brother'])->getNumberMultimediaObjects());

        $addedTags = $this->tagService->addTagToMultimediaObject($mmobj, $parentTag->getId());
        static::assertCount(2, $addedTags);
        static::assertCount(2, $this->mmobjRepo->find($mmobj->getId())->getTags());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'tag1'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'parent'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'brother'])->getNumberMultimediaObjects());

        $removedTags = $this->tagService->removeTagFromMultimediaObject($mmobj, $parentTag->getId());
        static::assertCount(2, $removedTags);
        static::assertCount(0, $this->mmobjRepo->find($mmobj->getId())->getTags());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'tag1'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'parent'])->getNumberMultimediaObjects());
        static::assertEquals(0, $this->tagRepo->findOneBy(['cod' => 'brother'])->getNumberMultimediaObjects());
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

        static::assertCount(5, $this->mmobjRepo->find($mmobj1->getId())->getTags());
        static::assertCount(3, $this->mmobjRepo->find($mmobj2->getId())->getTags());
        static::assertCount(0, $this->mmobjRepo->find($mmobj3->getId())->getTags());
        static::assertEquals(1, $tag1->getNumberMultimediaObjects());
        static::assertEquals(2, $tag2->getNumberMultimediaObjects());
        static::assertEquals(1, $tag3->getNumberMultimediaObjects());

        $this->tagService->resetTags([$mmobj1, $mmobj2, $mmobj3], $mmobj1->getTags()->toArray());

        static::assertCount(5, $this->mmobjRepo->find($mmobj1->getId())->getTags());
        static::assertCount(5, $this->mmobjRepo->find($mmobj2->getId())->getTags());
        static::assertCount(5, $this->mmobjRepo->find($mmobj3->getId())->getTags());
        static::assertEquals(3, $tag1->getNumberMultimediaObjects());
        static::assertEquals(3, $tag2->getNumberMultimediaObjects());
        static::assertEquals(3, $tag3->getNumberMultimediaObjects());

        $this->tagService->resetTags([$mmobj1, $mmobj2, $mmobj3], []);

        static::assertCount(0, $this->mmobjRepo->find($mmobj1->getId())->getTags());
        static::assertCount(0, $this->mmobjRepo->find($mmobj2->getId())->getTags());
        static::assertCount(0, $this->mmobjRepo->find($mmobj3->getId())->getTags());

        static::assertEquals(0, $tag1->getNumberMultimediaObjects());
        static::assertEquals(0, $tag2->getNumberMultimediaObjects());
        static::assertEquals(0, $tag3->getNumberMultimediaObjects());
    }

    public function testSyncTags()
    {
        $mmobj1 = $this->createMultimediaObject('mmobj1');
        $mmobj2 = $this->createMultimediaObject('mmobj2');
        $mmobj3 = $this->createMultimediaObject('mmobj3');
        $tag1 = $this->createTag('tag1');
        $tag11 = $this->createTag('tag11', $tag1);
        $tag111 = $this->createTag('tag111', $tag11);
        $tag12 = $this->createTag('tag12', $tag1);
        $tag2 = $this->createTag('tag2');
        $tag21 = $this->createTag('tag21', $tag2);
        $tag22 = $this->createTag('tag22', $tag2);
        $tag3 = $this->createTag('tag3');
        $tag31 = $this->createTag('tag31', $tag3);
        $tag32 = $this->createTag('tag32', $tag3);

        $this->tagService->addTag($mmobj1, $tag1);
        $this->tagService->addTag($mmobj1, $tag11);
        $this->tagService->addTag($mmobj1, $tag111);
        $this->tagService->addTag($mmobj1, $tag2);
        $this->tagService->addTag($mmobj1, $tag21);
        $this->tagService->addTag($mmobj1, $tag3);
        $this->tagService->addTag($mmobj1, $tag31);
        $this->tagService->addTag($mmobj2, $tag2);
        $this->tagService->addTag($mmobj3, $tag3);
        $this->tagService->addTag($mmobj3, $tag32);

        $this->tagService->syncTagsForCollections(
            [$mmobj1, $mmobj2, $mmobj3],
            $mmobj1->getTags()->toArray(),
            [$tag1, $tag2]
        );

        static::assertEquals(3, $tag1->getNumberMultimediaObjects());
        static::assertEquals(3, $tag2->getNumberMultimediaObjects());
        static::assertEquals(3, $tag111->getNumberMultimediaObjects());
        static::assertEquals(1, $tag31->getNumberMultimediaObjects());
        static::assertEquals(1, $tag32->getNumberMultimediaObjects());
    }

    public function testTagFindNotExistInRemove()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not found');
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

        static::assertCount(5, $this->mmobjRepo->find($mmobj1->getId())->getTags());
        static::assertCount(3, $this->mmobjRepo->find($mmobj2->getId())->getTags());
        static::assertCount(0, $this->mmobjRepo->find($mmobj3->getId())->getTags());
        static::assertEquals(0, $tag1->getNumberMultimediaObjects());
        static::assertEquals(1, $tag2->getNumberMultimediaObjects());
        static::assertEquals(0, $tag3->getNumberMultimediaObjects());

        $this->tagService->resetTags([$mmobj1, $mmobj2, $mmobj3], $mmobj1->getTags()->toArray());

        static::assertCount(5, $this->mmobjRepo->find($mmobj1->getId())->getTags());
        static::assertCount(5, $this->mmobjRepo->find($mmobj2->getId())->getTags());
        static::assertCount(5, $this->mmobjRepo->find($mmobj3->getId())->getTags());
        static::assertEquals(2, $tag1->getNumberMultimediaObjects());
        static::assertEquals(2, $tag2->getNumberMultimediaObjects());
        static::assertEquals(2, $tag3->getNumberMultimediaObjects());

        $this->tagService->resetTags([$mmobj1, $mmobj2, $mmobj3], []);

        static::assertCount(0, $this->mmobjRepo->find($mmobj1->getId())->getTags());
        static::assertCount(0, $this->mmobjRepo->find($mmobj2->getId())->getTags());
        static::assertCount(0, $this->mmobjRepo->find($mmobj3->getId())->getTags());

        static::assertEquals(0, $tag1->getNumberMultimediaObjects());
        static::assertEquals(0, $tag2->getNumberMultimediaObjects());
        static::assertEquals(0, $tag3->getNumberMultimediaObjects());
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
        static::assertEquals($newTitle, $updatedTag->getTitle());
        static::assertNotEquals($lastUpdatedDate, $updatedTag->getUpdated());
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
                static::assertEquals($firstTitle, $embeddedTag->getTitle());
            }
        }

        $lastUpdatedDate = $tag->getUpdated();

        $newTitle = 'Tag number 1';
        $tag->setTitle($newTitle);

        $tag = $this->tagService->updateTag($tag);

        $this->dm->clear();

        $multimediaObject = $this->mmobjRepo->find($multimediaObject->getId());
        $tag = $this->tagRepo->find($tag->getId());

        $embeddedTags = $multimediaObject->getTags();
        foreach ($embeddedTags as $embeddedTag) {
            if ($embeddedTag->getId() === $tag->getId()) {
                static::assertEquals($newTitle, $embeddedTag->getTitle());
                static::assertNotEquals($lastUpdatedDate, $embeddedTag->getUpdated());
                static::assertNotEquals($lastUpdatedDate, $tag->getUpdated());
            }
        }
    }

    public function testDeleteUsedTag()
    {
        $this->expectException(\Exception::class);
        $tag = $this->createTagWithTree('tag1');
        $series = $this->factoryService->createSeries();
        $mmObject0 = $this->factoryService->createMultimediaObject($series);
        $this->tagService->addTag($mmObject0, $tag);
        $this->tagService->deleteTag($tag);
    }

    public function testTagsInPrototype()
    {
        $tag = $this->createTagWithTree('tag1');
        $broTag = $this->tagRepo->findOneBy(['cod' => 'brother']);

        $series = $this->factoryService->createSeries();
        $mmObject0 = $this->factoryService->createMultimediaObject($series);
        $this->dm->persist($series);
        $this->dm->persist($mmObject0);
        $this->dm->flush();
        static::assertCount(0, $mmObject0->getTags());

        $prototype = $this->mmobjRepo->findPrototype($series);
        $this->tagService->addTag($prototype, $tag);

        $mmObject1 = $this->factoryService->createMultimediaObject($series);
        static::assertCount(3, $mmObject1->getTags());

        $this->tagService->addTag($prototype, $broTag);
        $this->tagService->deleteTag($broTag);

        $mmObject2 = $this->factoryService->createMultimediaObject($series);
        static::assertCount(3, $mmObject2->getTags());
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
        $mmobj->setNumericalID(random_int(0, 10000));
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

    private function createTag($cod, $parentTag = null)
    {
        $locale = 'en';

        if (!$parentTag) {
            $parentTag = $this->tagRepo->findOneBy(['cod' => 'ROOT']);
            if (!$parentTag) {
                $parentTag = new Tag();
                $parentTag->setCod('ROOT');
                $this->dm->persist($parentTag);
            }
        }

        $tag = new Tag();
        $tag->setLocale($locale);
        $tag->setCod($cod);
        $tag->setTitle(ucfirst($cod));
        $tag->setParent($parentTag);
        $this->dm->persist($tag);
        $this->dm->flush();

        return $tag;
    }

    private function createTagWithTree($cod, $withROOT = true)
    {
        if ($withROOT) {
            $rootTag = $this->tagRepo->findOneBy(['cod' => 'ROOT']);
            if (null === $rootTag) {
                $rootTag = new Tag();
                $rootTag->setCod('ROOT');
                $this->dm->persist($rootTag);
            }
        } else {
            $rootTag = $this->tagRepo->findOneBy(['cod' => 'grandparent']);
            if (null === $rootTag) {
                $rootTag = new Tag();
                $rootTag->setCod('grandparent');
                $this->dm->persist($rootTag);
            }
        }

        $locale = 'en';

        $parentTag = $this->tagRepo->findOneBy(['cod' => 'parent']);
        if (null === $parentTag) {
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

        $broTag = $this->tagRepo->findOneBy(['cod' => 'brother']);
        if (null === $broTag) {
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
