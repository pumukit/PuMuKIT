<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Group;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Pumukit\SchemaBundle\Services\MultimediaObjectPicService;

class MultimediaObjectServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $tagRepo;
    private $factory;
    private $mmsService;
    private $tagService;

    public function __construct()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
          ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->tagRepo = $this->dm
          ->getRepository('PumukitSchemaBundle:Tag');
        $this->factory = static::$kernel->getContainer()
          ->get('pumukitschema.factory');
        $this->mmsService = static::$kernel->getContainer()
          ->get('pumukitschema.multimedia_object');
        $this->tagService = static::$kernel->getContainer()
          ->get('pumukitschema.tag');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Tag')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Group')->remove(array());
        $this->dm->flush();
    }

    public function testIsPublished()
    {
        $this->createTags();

        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        $webTVCode = 'PUCHWEBTV';
        $this->assertFalse($this->mmsService->isPublished($mm, $webTVCode));
        
        $webTVTag = $this->tagRepo->findOneByCod($webTVCode);
        $addedTags = $this->tagService->addTagToMultimediaObject($mm, $webTVTag->getId());

        $this->assertFalse($this->mmsService->isPublished($mm, $webTVCode));

        $mm->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($this->mmsService->isPublished($mm, $webTVCode));

        $mm->setStatus(MultimediaObject::STATUS_HIDE);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($this->mmsService->isPublished($mm, $webTVCode));
    }

    public function testIsHidden()
    {
        $this->createTags();

        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        $webTVCode = 'PUCHWEBTV';
        $this->assertFalse($this->mmsService->isHidden($mm, $webTVCode));
        
        $webTVTag = $this->tagRepo->findOneByCod($webTVCode);
        $addedTags = $this->tagService->addTagToMultimediaObject($mm, $webTVTag->getId());

        $this->assertFalse($this->mmsService->isHidden($mm, $webTVCode));

        $mm->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($this->mmsService->isHidden($mm, $webTVCode));

        $mm->setStatus(MultimediaObject::STATUS_HIDE);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($this->mmsService->isHidden($mm, $webTVCode));

        $mm->setStatus(MultimediaObject::STATUS_BLOQ);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($this->mmsService->isHidden($mm, $webTVCode));
    }

    public function testHasPlayableResource()
    {
        $series = $this->factory->createSeries();
        $mm1 = $this->factory->createMultimediaObject($series);

        $track1 = new Track();
        $mm1->addTrack($track1);
        $this->dm->persist($mm1);
        $this->dm->flush();

        $this->assertFalse($this->mmsService->hasPlayableResource($mm1));

        $track2 = new Track();
        $track2->addTag('display');
        $mm1->addTrack($track2);
        $this->dm->persist($mm1);
        $this->dm->flush();

        $this->assertTrue($this->mmsService->hasPlayableResource($mm1));

        $mm2 = $this->factory->createMultimediaObject($series);

        $this->assertFalse($this->mmsService->hasPlayableResource($mm2));

        $mm2->setProperty('opencast', 'opencast_id');
        $this->dm->persist($mm2);
        $this->dm->flush();

        $this->assertTrue($this->mmsService->hasPlayableResource($mm2));
    }

    public function testCanBeDisplayed()
    {
        $this->createTags();

        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        $webTVCode = 'PUCHWEBTV';
        
        $webTVTag = $this->tagRepo->findOneByCod($webTVCode);
        $addedTags = $this->tagService->addTagToMultimediaObject($mm, $webTVTag->getId());

        $mm->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($this->mmsService->canBeDisplayed($mm, $webTVCode));

        $mm->setProperty('opencast', 'opencast_id');
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($this->mmsService->canBeDisplayed($mm, $webTVCode));
    }

    public function testResetMagicUrl()
    {
        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        $secret = $mm->getSecret();

        $this->assertNotEquals($secret, $this->mmsService->resetMagicUrl($mm));
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
        $this->assertEquals($newTitle, $multimediaObject->getTitle());
    }

    public function testIncNumView()
    {
        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        $this->assertEquals(0, $mm->getNumView());

        $this->mmsService->incNumView($mm);
        $this->assertEquals(1, $mm->getNumView());
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
        $multimediaObject->setTitle('test');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($group3);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->assertEquals(0, count($multimediaObject->getGroups()));
        $this->assertFalse($multimediaObject->containsGroup($group1));
        $this->assertFalse($multimediaObject->containsGroup($group2));
        $this->assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->addGroup($group1, $multimediaObject);

        $this->assertEquals(1, count($multimediaObject->getGroups()));
        $this->assertTrue($multimediaObject->containsGroup($group1));
        $this->assertFalse($multimediaObject->containsGroup($group2));
        $this->assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->addGroup($group2, $multimediaObject);

        $this->assertEquals(2, count($multimediaObject->getGroups()));
        $this->assertTrue($multimediaObject->containsGroup($group1));
        $this->assertTrue($multimediaObject->containsGroup($group2));
        $this->assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->addGroup($group3, $multimediaObject);

        $this->assertEquals(3, count($multimediaObject->getGroups()));
        $this->assertTrue($multimediaObject->containsGroup($group1));
        $this->assertTrue($multimediaObject->containsGroup($group2));
        $this->assertTrue($multimediaObject->containsGroup($group3));
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
        $multimediaObject->setTitle('test');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($group3);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->assertEquals(0, count($multimediaObject->getGroups()));
        $this->assertFalse($multimediaObject->containsGroup($group1));
        $this->assertFalse($multimediaObject->containsGroup($group2));
        $this->assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->addGroup($group1, $multimediaObject);

        $multimediaObject = $this->repo->find($multimediaObject->getId());

        $this->assertEquals(1, count($multimediaObject->getGroups()));
        $this->assertTrue($multimediaObject->containsGroup($group1));
        $this->assertFalse($multimediaObject->containsGroup($group2));
        $this->assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->deleteGroup($group1, $multimediaObject);

        $multimediaObject = $this->repo->find($multimediaObject->getId());

        $this->assertEquals(0, count($multimediaObject->getGroups()));
        $this->assertFalse($multimediaObject->containsGroup($group1));
        $this->assertFalse($multimediaObject->containsGroup($group2));
        $this->assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->deleteGroup($group2, $multimediaObject);

        $multimediaObject = $this->repo->find($multimediaObject->getId());

        $this->assertEquals(0, count($multimediaObject->getGroups()));
        $this->assertFalse($multimediaObject->containsGroup($group1));
        $this->assertFalse($multimediaObject->containsGroup($group2));
        $this->assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->addGroup($group3, $multimediaObject);

        $multimediaObject = $this->repo->find($multimediaObject->getId());

        $this->assertEquals(1, count($multimediaObject->getGroups()));
        $this->assertFalse($multimediaObject->containsGroup($group1));
        $this->assertFalse($multimediaObject->containsGroup($group2));
        $this->assertTrue($multimediaObject->containsGroup($group3));

        $this->mmsService->deleteGroup($group1, $multimediaObject);

        $multimediaObject = $this->repo->find($multimediaObject->getId());

        $this->assertEquals(1, count($multimediaObject->getGroups()));
        $this->assertFalse($multimediaObject->containsGroup($group1));
        $this->assertFalse($multimediaObject->containsGroup($group2));
        $this->assertTrue($multimediaObject->containsGroup($group3));

        $this->mmsService->deleteGroup($group3, $multimediaObject);

        $multimediaObject = $this->repo->find($multimediaObject->getId());

        $this->assertEquals(0, count($multimediaObject->getGroups()));
        $this->assertFalse($multimediaObject->containsGroup($group1));
        $this->assertFalse($multimediaObject->containsGroup($group2));
        $this->assertFalse($multimediaObject->containsGroup($group3));
    }
}