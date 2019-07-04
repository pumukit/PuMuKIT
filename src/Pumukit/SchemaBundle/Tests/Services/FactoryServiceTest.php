<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Services\FactoryService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class FactoryServiceTest extends WebTestCase
{
    private $dm;
    private $mmobjRepo;
    private $seriesRepo;
    private $translator;
    private $factory;
    private $locales;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->seriesRepo = $this->dm
            ->getRepository(Series::class)
        ;
        $this->mmobjRepo = $this->dm
            ->getRepository(MultimediaObject::class)
        ;
        $this->translator = static::$kernel->getContainer()
            ->get('translator')
        ;
        $this->factory = static::$kernel->getContainer()
            ->get('pumukitschema.factory')
        ;
        $this->locales = $this->factory->getLocales();

        $this->dm->getDocumentCollection(MultimediaObject::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection('PumukitSchemaBundle:SeriesType')
            ->remove([])
        ;
        $this->dm->getDocumentCollection(Series::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection(Role::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection(Tag::class)
            ->remove([])
        ;
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->seriesRepo = null;
        $this->mmobjRepo = null;
        $this->translator = null;
        $this->factory = null;
        $this->locales = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testCreateSeries()
    {
        $series = $this->factory->createSeries();

        $this->assertEquals(1, count($this->seriesRepo->findAll()));
        $this->assertEquals(1, count($this->mmobjRepo->findAll()));
        $this->assertEquals($series, $this->mmobjRepo->findAll()[0]->getSeries());
        //NOTE getMultimediaObjects gives us all multimedia objects in the series except prototype
        $this->assertEquals($series, $this->seriesRepo->findAll()[0]);
        $this->assertEquals(0, count($this->seriesRepo->getMultimediaObjects($series)));

        //NOTE series.multimedia_objects have diferent internal initialized value.
        //$this->assertEquals($series, $this->mmobjRepo->findAll()[0]->getSeries());
        $this->assertEquals($series->getId(), $this->mmobjRepo->findAll()[0]->getSeries()->getId());
        $this->assertTrue($this->mmobjRepo->findAll()[0]->isPrototype());
    }

    public function testCreateMultimediaObject()
    {
        $series = $this->factory->createSeries();
        $mmobj = $this->factory->createMultimediaObject($series);

        $this->assertEquals(1, count($this->seriesRepo->findAll()));
        $this->assertEquals(2, count($this->mmobjRepo->findAll()));
        $this->assertEquals($series, $this->seriesRepo->findAll()[0]);
        $this->assertEquals($series->getId(), $this->mmobjRepo->findAll()[0]->getSeries()->getId());
        $this->assertEquals($series->getId(), $this->mmobjRepo->find($mmobj->getId())->getSeries()->getId());

        $this->assertEquals(1, count($this->mmobjRepo->findWithoutPrototype($series)));
        $this->assertEquals(1, count($this->seriesRepo->getMultimediaObjects($series)));
        $this->assertEquals($mmobj, $this->seriesRepo->getMultimediaObjects($series)->getSingleResult());

        $this->assertEquals($mmobj->getStatus(), MultimediaObject::STATUS_BLOCKED);
    }

    public function testUpdateMultimediaObjectTemplate()
    {
        $series = $this->factory->createSeries();

        $mmobj = $this->factory->createMultimediaObject($series);

        $mmobjTemplate = $this->mmobjRepo->findPrototype($series);
        foreach ($this->locales as $locale) {
            $keyword = $this->translator->trans('keytest', [], null, $locale);
            $mmobjTemplate->setKeyword($keyword, $locale);
        }
        $this->dm->persist($mmobjTemplate);

        $mmobj2 = $this->factory->createMultimediaObject($series);
        $this->dm->persist($mmobj2);
        $this->dm->flush();

        foreach ($this->locales as $locale) {
            $this->assertNotEquals($mmobj->getKeyword($locale), $this->mmobjRepo->findPrototype($series)->getKeyword($locale));
            $this->assertEquals($mmobj2->getKeyword($locale), $this->mmobjRepo->findPrototype($series)->getKeyword($locale));
        }
    }

    public function testSeriesType()
    {
        $series_type1 = new SeriesType();
        $name_type1 = 'Series type 1';
        $series_type1->setName($name_type1);

        $series_type2 = new SeriesType();
        $name_type2 = 'Series type 2';
        $series_type2->setName($name_type2);

        $this->dm->persist($series_type1);
        $this->dm->persist($series_type2);
        $this->dm->flush();

        //Workaround to fix reference method initialization.
        $this->dm->clear(get_class($series_type1));
        $series_type1 = $this->dm->find('PumukitSchemaBundle:SeriesType', $series_type1->getId());
        $series_type2 = $this->dm->find('PumukitSchemaBundle:SeriesType', $series_type2->getId());

        $series1 = $this->factory->createSeries();
        $name1 = 'Series 1';
        $series1->setTitle($name1);

        $series2 = $this->factory->createSeries();
        $name2 = 'Series 2';
        $series2->setTitle($name2);

        $series3 = $this->factory->createSeries();
        $name3 = 'Series 3';
        $series3->setTitle($name3);

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $series1->setSeriesType($series_type1);
        $series2->setSeriesType($series_type1);
        $series3->setSeriesType($series_type2);

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $this->assertEquals(2, count($series_type1->getSeries()));
        $this->assertEquals(1, count($series_type2->getSeries()));
    }

    public function testFindSeriesById()
    {
        $series = $this->factory->createSeries();

        $this->assertEquals($series, $this->factory->findSeriesById($series->getId(), null));
        $this->assertEquals($series, $this->factory->findSeriesById(null, $series->getId()));
        $this->assertEquals(null, $this->factory->findSeriesById(null, null));
    }

    public function testFindMultimediaObjectById()
    {
        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        $this->assertEquals($mm, $this->factory->findMultimediaObjectById($mm->getId()));
    }

    public function testGetParentTags()
    {
        $tag = new Tag();
        $tag->setCod('ROOT');

        $this->dm->persist($tag);
        $this->dm->flush();

        $tagA = new Tag();
        $tagA->setCod('A');
        $tagA->setParent($tag);
        $this->dm->persist($tagA);

        $tagB = new Tag();
        $tagB->setCod('B');
        $tagB->setParent($tag);
        $this->dm->persist($tagB);

        $tagB1 = new Tag();
        $tagB1->setCod('B1');
        $tagB1->setParent($tagB);
        $this->dm->persist($tagB1);

        $tagB2 = new Tag();
        $tagB2->setCod('B2');
        $tagB2->setParent($tagB);
        $this->dm->persist($tagB2);

        $tagB2A = new Tag();
        $tagB2A->setCod('B2A');
        $tagB2A->setParent($tagB2);
        $this->dm->persist($tagB2A);

        $this->dm->flush();

        $this->assertEquals(2, count($this->factory->getParentTags()));
    }

    public function testGetMultimediaObjectTemplate()
    {
        $series = $this->factory->createSeries();

        $this->assertTrue($this->factory->getMultimediaObjectPrototype($series)->isPrototype());
    }

    public function testGetTagsByCod()
    {
        $tagA = new Tag();
        $tagA->setCod('A');
        $this->dm->persist($tagA);
        $this->dm->flush();

        $tagB = new Tag();
        $tagB->setCod('B');
        $this->dm->persist($tagB);
        $this->dm->flush();

        $tagC = new Tag();
        $tagC->setCod('C');
        $this->dm->persist($tagC);
        $this->dm->flush();

        $this->assertEquals($tagA, $this->factory->getTagsByCod('A', false));

        $tagB->setParent($tagA);
        $tagC->setParent($tagA);

        $this->assertEquals(2, count($this->factory->getTagsByCod('A', true)));
    }

    public function testDeleteSeries()
    {
        $series = $this->factory->createSeries();
        $mmobj = $this->factory->createMultimediaObject($series);

        $this->assertEquals(1, count($this->seriesRepo->findAll()));
        $this->assertEquals(2, count($this->mmobjRepo->findAll()));

        $this->factory->deleteSeries($series);

        $this->assertEquals(0, count($this->seriesRepo->findAll()));
        $this->assertEquals(0, count($this->mmobjRepo->findAll()));
    }

    public function testDeleteResource()
    {
        $series = $this->factory->createSeries();
        $mmobj = $this->factory->createMultimediaObject($series);

        $this->assertEquals(1, count($this->seriesRepo->findAll()));
        $this->assertEquals(2, count($this->mmobjRepo->findAll()));

        $this->factory->deleteMultimediaObject($mmobj);

        $this->assertEquals(1, count($this->mmobjRepo->findAll()));

        $this->factory->deleteSeries($series);

        $this->assertEquals(0, count($this->seriesRepo->findAll()));
        $this->assertEquals(0, count($this->mmobjRepo->findAll()));

        $role = new Role();
        $role->setCod('role');
        $this->dm->persist($role);
        $this->dm->flush();

        $this->assertEquals(1, count($this->dm->getRepository(Role::class)->findAll()));
        $this->factory->deleteResource($role);
        $this->assertEquals(0, count($this->dm->getRepository(Role::class)->findAll()));
    }

    public function testClone()
    {
        $series = $this->factory->createSeries();
        $src = $this->factory->createMultimediaObject($series);

        $tagA = new Tag();
        $tagA->setCod('A');
        $this->dm->persist($tagA);
        $this->dm->flush();

        $tagB = new Tag();
        $tagB->setCod('B');
        $this->dm->persist($tagB);
        $this->dm->flush();

        $personA = new Person();
        $personB = new Person();

        $roleA = new Role();
        $roleB = new Role();

        $src->addTag($tagA);
        $src->addTag($tagB);
        $src->addPersonWithRole($personA, $roleA);
        $src->addPersonWithRole($personB, $roleB);

        $new = $this->factory->cloneMultimediaObject($src);

        $newTitles = $new->getI18nTitle();
        foreach ($src->getI18nTitle() as $key => $title) {
            $string = $this->translator->trans('cloned', [], null, $key);
            $title = $title.' ('.$string.')';
            $this->assertEquals($newTitles[$key], $title);
        }
        $this->assertTrue($src->getRank() < $new->getRank());
        $this->assertEquals($new->getI18nSubtitle(), $src->getI18nSubtitle());
        $this->assertEquals($new->getI18nDescription(), $src->getI18nDescription());
        $this->assertEquals($new->getI18nLine2(), $src->getI18nLine2());
        $this->assertEquals($new->getI18nKeyword(), $src->getI18nKeyword());
        $this->assertEquals($new->getCopyright(), $src->getCopyright());
        $this->assertEquals($new->getLicense(), $src->getLicense());
        $this->assertEquals($new->getPublicDate(), $src->getPublicDate());
        $this->assertEquals($new->getRecordDate(), $src->getRecordDate());
        $this->assertEquals($new->getStatus(), MultimediaObject::STATUS_BLOCKED);
        $this->assertEquals($new->getEmbeddedBroadcast()->getType(), $src->getEmbeddedBroadcast()->getType());
        $this->assertEquals($new->getEmbeddedBroadcast()->getName(), $src->getEmbeddedBroadcast()->getName());
        $this->assertEquals($new->getEmbeddedBroadcast()->getPassword(), $src->getEmbeddedBroadcast()->getPassword());
        $this->assertEquals(count($new->getEmbeddedBroadcast()->getGroups()), count($src->getEmbeddedBroadcast()->getGroups()));
        foreach ($src->getEmbeddedBroadcast()->getGroups() as $group) {
            $this->assertTrue($new->getEmbeddedBroadcast()->containsGroup($group));
        }
        $this->assertEquals(count($new->getRoles()), count($src->getRoles()));
        $this->assertEquals(count($new->getTags()), count($src->getTags()));
    }

    public function testGetDefaultMultimediaObjectI18nTitle()
    {
        $i18nTitle = [];
        foreach ($this->factory->getLocales() as $locale) {
            $i18nTitle[$locale] = FactoryService::DEFAULT_MULTIMEDIAOBJECT_TITLE;
        }

        $this->assertEquals($i18nTitle, $this->factory->getDefaultMultimediaObjectI18nTitle());
    }

    public function testGetDefaultSeriesI18nTitle()
    {
        $i18nTitle = [];
        foreach ($this->factory->getLocales() as $locale) {
            $i18nTitle[$locale] = FactoryService::DEFAULT_SERIES_TITLE;
        }

        $this->assertEquals($i18nTitle, $this->factory->getDefaultSeriesI18nTitle());
    }
}
