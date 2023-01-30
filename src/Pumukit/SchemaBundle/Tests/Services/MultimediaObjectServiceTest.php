<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\User;

/**
 * @internal
 *
 * @coversNothing
 */
class MultimediaObjectServiceTest extends PumukitTestCase
{
    private $repo;
    private $tagRepo;
    private $factory;
    private $mmsService;
    private $tagService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();

        $this->repo = $this->dm->getRepository(MultimediaObject::class);
        $this->tagRepo = $this->dm->getRepository(Tag::class);
        $this->factory = static::$kernel->getContainer()->get('pumukitschema.factory');
        $this->mmsService = static::$kernel->getContainer()->get('pumukitschema.multimedia_object');
        $this->tagService = static::$kernel->getContainer()->get('pumukitschema.tag');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        $this->tagRepo = null;
        $this->factory = null;
        $this->mmsService = null;
        $this->tagService = null;
        gc_collect_cycles();
    }

    public function testIsPublished()
    {
        $this->createTags();

        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        $webTVCode = 'PUCHWEBTV';
        static::assertFalse($this->mmsService->isPublished($mm, $webTVCode));

        $webTVTag = $this->tagRepo->findOneBy(['cod' => $webTVCode]);
        $this->tagService->addTagToMultimediaObject($mm, $webTVTag->getId());

        static::assertFalse($this->mmsService->isPublished($mm, $webTVCode));

        $mm->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertTrue($this->mmsService->isPublished($mm, $webTVCode));

        $mm->setStatus(MultimediaObject::STATUS_HIDDEN);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertFalse($this->mmsService->isPublished($mm, $webTVCode));
    }

    public function testIsHidden()
    {
        $this->createTags();

        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        $webTVCode = 'PUCHWEBTV';
        static::assertFalse($this->mmsService->isHidden($mm, $webTVCode));

        $webTVTag = $this->tagRepo->findOneBy(['cod' => $webTVCode]);
        $this->tagService->addTagToMultimediaObject($mm, $webTVTag->getId());

        static::assertFalse($this->mmsService->isHidden($mm, $webTVCode));

        $mm->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertTrue($this->mmsService->isHidden($mm, $webTVCode));

        $mm->setStatus(MultimediaObject::STATUS_HIDDEN);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertTrue($this->mmsService->isHidden($mm, $webTVCode));

        $mm->setStatus(MultimediaObject::STATUS_BLOCKED);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertFalse($this->mmsService->isHidden($mm, $webTVCode));
    }

    public function testHasPlayableResource()
    {
        $series = $this->factory->createSeries();
        $mm1 = $this->factory->createMultimediaObject($series);

        $track1 = new Track();
        $mm1->addTrack($track1);
        $this->dm->persist($mm1);
        $this->dm->flush();

        static::assertFalse($this->mmsService->hasPlayableResource($mm1));

        $track2 = new Track();
        $track2->addTag('display');
        $mm1->addTrack($track2);
        $this->dm->persist($mm1);
        $this->dm->flush();

        static::assertTrue($this->mmsService->hasPlayableResource($mm1));

        $mm2 = $this->factory->createMultimediaObject($series);

        static::assertFalse($this->mmsService->hasPlayableResource($mm2));

        $track2->addTag('presenter/delivery');
        $mm2->addTrack($track2);
        $this->dm->persist($mm2);
        $this->dm->flush();

        static::assertTrue($this->mmsService->hasPlayableResource($mm2));
    }

    public function testCanBeDisplayed()
    {
        $this->createTags();

        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        $webTVCode = 'PUCHWEBTV';

        $webTVTag = $this->tagRepo->findOneBy(['cod' => $webTVCode]);
        $this->tagService->addTagToMultimediaObject($mm, $webTVTag->getId());

        $mm->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm->setProperty('opencast', 'opencast_id');
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertFalse($this->mmsService->canBeDisplayed($mm, $webTVCode));
    }

    public function testResetMagicUrl()
    {
        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        $secret = $mm->getSecret();

        static::assertNotEquals($secret, $this->mmsService->resetMagicUrl($mm));
    }

    public function testUpdateMultimediaObject()
    {
        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        $title = 'First title';
        $mm->setTitle($title);
        $this->dm->persist($mm);
        $this->dm->flush();

        $newTitle = 'New Title';
        $mm->setTitle($newTitle);
        $mm = $this->mmsService->updateMultimediaObject($mm);

        $multimediaObject = $this->repo->find($mm->getId());
        static::assertEquals($newTitle, $multimediaObject->getTitle());
    }

    public function testIncNumView()
    {
        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        static::assertEquals(0, $mm->getNumView());

        $this->mmsService->incNumView($mm);
        static::assertEquals(1, $mm->getNumView());
    }

    public function testAddGroup()
    {
        $group1 = new Group();
        $group1->setKey('key1');
        $group1->setName('name1');

        $group2 = new Group();
        $group2->setKey('key2');
        $group2->setName('name2');

        $group3 = new Group();
        $group3->setKey('key3');
        $group3->setName('name3');

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setTitle('test');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($group3);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        static::assertCount(0, $multimediaObject->getGroups());
        static::assertFalse($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->addGroup($group1, $multimediaObject);

        static::assertCount(1, $multimediaObject->getGroups());
        static::assertTrue($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->addGroup($group2, $multimediaObject);

        static::assertCount(2, $multimediaObject->getGroups());
        static::assertTrue($multimediaObject->containsGroup($group1));
        static::assertTrue($multimediaObject->containsGroup($group2));
        static::assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->addGroup($group3, $multimediaObject);

        static::assertCount(3, $multimediaObject->getGroups());
        static::assertTrue($multimediaObject->containsGroup($group1));
        static::assertTrue($multimediaObject->containsGroup($group2));
        static::assertTrue($multimediaObject->containsGroup($group3));
    }

    public function testDeleteGroup()
    {
        $group1 = new Group();
        $group1->setKey('key1');
        $group1->setName('name1');

        $group2 = new Group();
        $group2->setKey('key2');
        $group2->setName('name2');

        $group3 = new Group();
        $group3->setKey('key3');
        $group3->setName('name3');

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setTitle('test');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($group3);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        static::assertCount(0, $multimediaObject->getGroups());
        static::assertFalse($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->addGroup($group1, $multimediaObject);

        $multimediaObject = $this->repo->find($multimediaObject->getId());

        static::assertCount(1, $multimediaObject->getGroups());
        static::assertTrue($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->deleteGroup($group1, $multimediaObject);

        $multimediaObject = $this->repo->find($multimediaObject->getId());

        static::assertCount(0, $multimediaObject->getGroups());
        static::assertFalse($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->deleteGroup($group2, $multimediaObject);

        $multimediaObject = $this->repo->find($multimediaObject->getId());

        static::assertCount(0, $multimediaObject->getGroups());
        static::assertFalse($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->addGroup($group3, $multimediaObject);

        $multimediaObject = $this->repo->find($multimediaObject->getId());

        static::assertCount(1, $multimediaObject->getGroups());
        static::assertFalse($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertTrue($multimediaObject->containsGroup($group3));

        $this->mmsService->deleteGroup($group1, $multimediaObject);

        $multimediaObject = $this->repo->find($multimediaObject->getId());

        static::assertCount(1, $multimediaObject->getGroups());
        static::assertFalse($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertTrue($multimediaObject->containsGroup($group3));

        $this->mmsService->deleteGroup($group3, $multimediaObject);

        $multimediaObject = $this->repo->find($multimediaObject->getId());

        static::assertCount(0, $multimediaObject->getGroups());
        static::assertFalse($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertFalse($multimediaObject->containsGroup($group3));
    }

    public function testIsUserOwner()
    {
        $user1 = new User();
        $user1->setUsername('user1');
        $user1->setEmail('user1@mail.com');

        $user2 = new User();
        $user2->setUsername('user2');
        $user2->setEmail('user2@mail.com');

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();

        $owners1 = [];
        $owners2 = [$user1->getId()];
        $owners3 = [$user1->getId(), $user2->getId()];

        $mm1 = new MultimediaObject();
        $mm1->setNumericalID(1);
        $mm1->setTitle('mm1');
        $mm1->setProperty('owners', $owners1);

        $mm2 = new MultimediaObject();
        $mm2->setNumericalID(2);
        $mm2->setTitle('mm2');
        $mm2->setProperty('owners', $owners2);

        $mm3 = new MultimediaObject();
        $mm3->setNumericalID(3);
        $mm3->setTitle('mm3');
        $mm3->setProperty('owners', $owners3);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->flush();

        static::assertFalse($this->mmsService->isUserOwner($user1, $mm1));
        static::assertFalse($this->mmsService->isUserOwner($user2, $mm1));
        static::assertTrue($this->mmsService->isUserOwner($user1, $mm2));
        static::assertFalse($this->mmsService->isUserOwner($user2, $mm2));
        static::assertTrue($this->mmsService->isUserOwner($user1, $mm3));
        static::assertTrue($this->mmsService->isUserOwner($user2, $mm3));
    }

    public function testDeleteAllFromGroup()
    {
        $group = new Group();
        $group->setKey('key');
        $group->setName('group');
        $this->dm->persist($group);
        $this->dm->flush();

        static::assertCount(0, $this->repo->findWithGroup($group)->toArray());

        $mm1 = new MultimediaObject();
        $mm1->setNumericalID(1);
        $mm1->setTitle('mm1');
        $mm1->addGroup($group);

        $mm2 = new MultimediaObject();
        $mm2->setNumericalID(2);
        $mm2->setTitle('mm2');
        $mm2->addGroup($group);

        $mm3 = new MultimediaObject();
        $mm3->setNumericalID(3);
        $mm3->setTitle('mm3');
        $mm3->addGroup($group);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->flush();

        static::assertCount(3, $this->repo->findWithGroup($group)->toArray());

        $this->mmsService->deleteAllFromGroup($group);
        static::assertCount(0, $this->repo->findWithGroup($group)->toArray());
    }

    private function createTags()
    {
        $rootTag = new Tag();
        $rootTag->setCod('ROOT');
        $rootTag->setTitle('ROOT');
        $rootTag->setDisplay(false);
        $rootTag->setMetatag(true);

        $this->dm->persist($rootTag);
        $this->dm->flush();

        $pubChannelTag = new Tag();
        $pubChannelTag->setCod('PUBLICATIONCHANNELS');
        $pubChannelTag->setTitle('Publication Channels');
        $pubChannelTag->setDisplay(true);
        $pubChannelTag->setMetatag(true);
        $pubChannelTag->setParent($rootTag);

        $this->dm->persist($pubChannelTag);
        $this->dm->flush();

        $webTVTag = new Tag();
        $webTVTag->setCod('PUCHWEBTV');
        $webTVTag->setTitle('WebTV Publication Channel');
        $webTVTag->setDisplay(true);
        $webTVTag->setMetatag(false);
        $webTVTag->setParent($pubChannelTag);

        $this->dm->persist($webTVTag);
        $this->dm->flush();
    }
}
