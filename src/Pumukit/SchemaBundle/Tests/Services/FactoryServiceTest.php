<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Services\FactoryService;

/**
 * @internal
 * @coversNothing
 */
class FactoryServiceTest extends PumukitTestCase
{
    private $multimediaObjectRepo;
    private $seriesRepo;
    private $translator;
    private $factory;
    private $locales;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->seriesRepo = $this->dm->getRepository(Series::class);
        $this->multimediaObjectRepo = $this->dm->getRepository(MultimediaObject::class);
        $this->translator = static::$kernel->getContainer()->get('translator');
        $this->factory = static::$kernel->getContainer()->get('pumukitschema.factory');
        $this->locales = $this->factory->getLocales();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();
        $this->seriesRepo = null;
        $this->multimediaObjectRepo = null;
        $this->translator = null;
        $this->factory = null;
        $this->locales = null;
        gc_collect_cycles();
    }

    public function testCreateSeries(): void
    {
        $series = $this->factory->createSeries();

        static::assertCount(1, $this->seriesRepo->findAll());
        static::assertCount(1, $this->multimediaObjectRepo->findAll());
        static::assertEquals($series, $this->multimediaObjectRepo->findAll()[0]->getSeries());
        //NOTE getMultimediaObjects gives us all multimedia objects in the series except prototype
        static::assertEquals($series, $this->seriesRepo->findAll()[0]);
        static::assertCount(0, $this->seriesRepo->getMultimediaObjects($series));

        //NOTE series.multimedia_objects have diferent internal initialized value.
        //$this->assertEquals($series, $this->mmobjRepo->findAll()[0]->getSeries());
        static::assertEquals($series->getId(), $this->multimediaObjectRepo->findAll()[0]->getSeries()->getId());
        static::assertTrue($this->multimediaObjectRepo->findAll()[0]->isPrototype());
    }

    public function testCreateMultimediaObject(): void
    {
        $series = $this->factory->createSeries();
        $multimediaObject = $this->factory->createMultimediaObject($series);

        static::assertCount(1, $this->seriesRepo->findAll());
        static::assertCount(2, $this->multimediaObjectRepo->findAll());
        static::assertEquals($series, $this->seriesRepo->findAll()[0]);
        static::assertEquals($series->getId(), $this->multimediaObjectRepo->findAll()[0]->getSeries()->getId());
        static::assertEquals($series->getId(), $this->multimediaObjectRepo->find($multimediaObject->getId())->getSeries()->getId());

        static::assertCount(1, $this->multimediaObjectRepo->findWithoutPrototype($series));
        static::assertCount(1, $this->seriesRepo->getMultimediaObjects($series));
        static::assertEquals($multimediaObject, $this->seriesRepo->getMultimediaObjects($series)->toArray()[0]);

        static::assertEquals($multimediaObject->getStatus(), MultimediaObject::STATUS_BLOCKED);
    }

    public function testUpdateMultimediaObjectTemplate(): void
    {
        $series = $this->factory->createSeries();

        $multimediaObject = $this->factory->createMultimediaObject($series);

        $multimediaObjectTemplate = $this->multimediaObjectRepo->findPrototype($series);
        foreach ($this->locales as $locale) {
            $keyword = $this->translator->trans('keytest', [], null, $locale);
            $multimediaObjectTemplate->addKeyword($keyword, $locale);
        }
        $this->dm->persist($multimediaObjectTemplate);

        $multimediaObject2 = $this->factory->createMultimediaObject($series);
        $this->dm->persist($multimediaObject2);
        $this->dm->flush();

        foreach ($this->locales as $locale) {
            static::assertNotEquals($multimediaObject->getKeywordsAsString($locale), $this->multimediaObjectRepo->findPrototype($series)->getKeywordsAsString($locale));
            static::assertEquals($multimediaObject2->getKeywordsAsString($locale), $this->multimediaObjectRepo->findPrototype($series)->getKeywordsAsString($locale));
        }
    }

    public function testSeriesType(): void
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
        $this->dm->clear();
        $series_type1 = $this->dm->getRepository(SeriesType::class)->findOneBy(['_id' => $series_type1->getId()]);
        $series_type2 = $this->dm->getRepository(SeriesType::class)->findOneBy(['_id' => $series_type2->getId()]);

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

        static::assertCount(2, $series_type1->getSeries());
        static::assertCount(1, $series_type2->getSeries());
    }

    public function testFindSeriesById()
    {
        $series = $this->factory->createSeries();

        static::assertEquals($series, $this->factory->findSeriesById($series->getId(), null));
        static::assertEquals($series, $this->factory->findSeriesById(null, $series->getId()));
        static::assertEquals(null, $this->factory->findSeriesById(null, null));
    }

    public function testFindMultimediaObjectById(): void
    {
        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        static::assertEquals($mm, $this->factory->findMultimediaObjectById($mm->getId()));
    }

    public function testGetParentTags(): void
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

        static::assertCount(2, $this->factory->getParentTags());
    }

    public function testGetMultimediaObjectTemplate()
    {
        $series = $this->factory->createSeries();

        static::assertTrue($this->factory->getMultimediaObjectPrototype($series)->isPrototype());
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

        static::assertEquals($tagA, $this->factory->getTagsByCod('A', false));

        $tagB->setParent($tagA);
        $tagC->setParent($tagA);

        static::assertCount(2, $this->factory->getTagsByCod('A', true));
    }

    public function testDeleteSeries(): void
    {
        $series = $this->factory->createSeries();
        $multimediaObject = $this->factory->createMultimediaObject($series);

        static::assertCount(1, $this->seriesRepo->findAll());
        static::assertCount(2, $this->multimediaObjectRepo->findAll());

        $this->factory->deleteSeries($series);

        static::assertCount(0, $this->seriesRepo->findAll());
        static::assertCount(0, $this->multimediaObjectRepo->findAll());
    }

    public function testDeleteResource(): void
    {
        $series = $this->factory->createSeries();
        $multimediaObject = $this->factory->createMultimediaObject($series);

        static::assertCount(1, $this->seriesRepo->findAll());
        static::assertCount(2, $this->multimediaObjectRepo->findAll());

        $this->factory->deleteMultimediaObject($multimediaObject);

        static::assertCount(1, $this->multimediaObjectRepo->findAll());

        $this->factory->deleteSeries($series);

        static::assertCount(0, $this->seriesRepo->findAll());
        static::assertCount(0, $this->multimediaObjectRepo->findAll());

        $role = new Role();
        $role->setCod('role');
        $this->dm->persist($role);
        $this->dm->flush();

        static::assertCount(1, $this->dm->getRepository(Role::class)->findAll());
        $this->factory->deleteResource($role);
        static::assertCount(0, $this->dm->getRepository(Role::class)->findAll());
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
            $title .= ' ('.$string.')';
            static::assertEquals($newTitles[$key], $title);
        }
        static::assertTrue($src->getRank() < $new->getRank());
        static::assertEquals($new->getI18nSubtitle(), $src->getI18nSubtitle());
        static::assertEquals($new->getI18nDescription(), $src->getI18nDescription());
        static::assertEquals($new->getI18nLine2(), $src->getI18nLine2());
        static::assertEquals($new->getI18nKeyword(), $src->getI18nKeyword());
        static::assertEquals($new->getCopyright(), $src->getCopyright());
        static::assertEquals($new->getLicense(), $src->getLicense());
        static::assertEquals($new->getPublicDate(), $src->getPublicDate());
        static::assertEquals($new->getRecordDate(), $src->getRecordDate());
        static::assertEquals($new->getStatus(), MultimediaObject::STATUS_BLOCKED);
        static::assertEquals($new->getEmbeddedBroadcast()->getType(), $src->getEmbeddedBroadcast()->getType());
        static::assertEquals($new->getEmbeddedBroadcast()->getName(), $src->getEmbeddedBroadcast()->getName());
        static::assertEquals($new->getEmbeddedBroadcast()->getPassword(), $src->getEmbeddedBroadcast()->getPassword());
        static::assertCount(is_countable($new->getEmbeddedBroadcast()->getGroups()) ? count($new->getEmbeddedBroadcast()->getGroups()) : 0, $src->getEmbeddedBroadcast()->getGroups());
        foreach ($src->getEmbeddedBroadcast()->getGroups() as $group) {
            static::assertTrue($new->getEmbeddedBroadcast()->containsGroup($group));
        }
        static::assertCount(is_countable($new->getRoles()) ? count($new->getRoles()) : 0, $src->getRoles());
        static::assertCount(is_countable($new->getTags()) ? count($new->getTags()) : 0, $src->getTags());
    }

    public function testGetDefaultMultimediaObjectI18nTitle(): void
    {
        $i18nTitle = [];
        foreach ($this->factory->getLocales() as $locale) {
            $i18nTitle[$locale] = FactoryService::DEFAULT_MULTIMEDIAOBJECT_TITLE;
        }

        static::assertEquals($i18nTitle, $this->factory->getDefaultMultimediaObjectI18nTitle());
    }

    public function testGetDefaultSeriesI18nTitle(): void
    {
        $i18nTitle = [];
        foreach ($this->factory->getLocales() as $locale) {
            $i18nTitle[$locale] = FactoryService::DEFAULT_SERIES_TITLE;
        }

        static::assertEquals($i18nTitle, $this->factory->getDefaultSeriesI18nTitle());
    }
}
