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
        $kernel = static::createKernel($options);
        $kernel->boot();
        $this->dm = $kernel->getContainer()
      ->get('doctrine_mongodb')->getManager();
        $this->tagRepo = $this->dm
      ->getRepository('PumukitSchemaBundle:Tag');
        $this->mmobjRepo = $this->dm
      ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->tagService = $kernel->getContainer()->get('pumukitschema.tag');

        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')
      ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Tag')
      ->remove(array());
    }

    public function testAddTagToMultimediaObject()
    {
        $mmobj = $this->createMultimediaObject('titulo cualquiera');
        $tag = $this->createTagWithTree('tag1');

        $this->assertEquals(0, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals($mmobj, $this->mmobjRepo->find($mmobj->getId()));
        $this->assertEquals($tag, $this->tagRepo->find($tag->getId()));

        $addedTags = $this->tagService->addTagToMultimediaObject($mmobj, $tag->getId());
        $this->assertEquals(3, count($this->mmobjRepo->find($mmobj->getId())->getTags()));
        $this->assertEquals(3, count($addedTags));
        $this->assertTrue($this->mmobjRepo->find($mmobj->getId())->containsTag($tag));
        $this->assertEquals(0, count($this->tagService->addTagToMultimediaObject($mmobj, $tag->getId())));
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

    private function createMultimediaObject($title)
    {
        $locale = 'en';
        $status = MultimediaObject::STATUS_NEW;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $subtitle = 'Subtitle paragraph';
        $description = "Description text";
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
        $rootTag = new Tag();
        if ($withROOT) {
            $rootTag->setCod('ROOT');
        } else {
            $rootTag->setCod('grandparent');
        }
        $this->dm->persist($rootTag);

        $locale = 'en';

        $parentTag = new Tag();
        $parentTag->setLocale($locale);
        $parentTag->setCod('parent');
        $parentTag->setTitle('Parent');
        $parentTag->setParent($rootTag);
        $this->dm->persist($parentTag);

        $tag = new Tag();
        $tag->setLocale($locale);
        $tag->setCod($cod);
        $tag->setTitle(ucfirst($cod));
        $tag->setParent($parentTag);
        $this->dm->persist($tag);

        $broTag = new Tag();
        $broTag->setLocale($locale);
        $broTag->setCod('brother');
        $broTag->setTitle('Brother');
        $broTag->setParent($parentTag);
        $this->dm->persist($broTag);

        $this->dm->flush();

        return $tag;
    }
}
