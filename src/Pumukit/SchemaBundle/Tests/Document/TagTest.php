<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Tag;

/**
 * @internal
 * @coversNothing
 */
class TagTest extends PumukitTestCase
{
    private $tagRepo;
    private $tagService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->tagRepo = $this->dm->getRepository(Tag::class);

        $this->tagService = static::$kernel->getContainer()->get('pumukitschema.tag');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->tagRepo = null;
        $this->tagService = null;
        gc_collect_cycles();
    }

    public function testGetterAndSetter()
    {
        $title = 'title';
        $description = 'description';
        $slug = 'slug';
        $cod = '23';
        $metatag = true;
        $created = new \DateTime('now');
        $updated = new \DateTime('now');
        $display = true;
        $youtubeProperty = 'w7dD-JJJytM&list=PLmXxqSJJq-yUfrjvKe5c5LX_1x7nGVF6c';
        $properties = ['youtube' => $youtubeProperty];

        $tag = new Tag();

        $tag->setTitle($title);
        $tag->setDescription($description);
        $tag->setSlug($slug);
        $tag->setCod($cod);
        $tag->setMetatag($metatag);
        $tag->setCreated($created);
        $tag->setUpdated($updated);
        $tag->setDisplay($display);
        $tag->setProperties($properties);

        $tag_parent = new Tag();
        $tag->setParent($tag_parent);

        static::assertEquals($title, $tag->getTitle());
        static::assertEquals($description, $tag->getDescription());
        static::assertEquals($slug, $tag->getSlug());
        static::assertEquals($cod, $tag->getCod());
        static::assertEquals($metatag, $tag->getMetatag());
        static::assertEquals($created, $tag->getCreated());
        static::assertEquals($updated, $tag->getUpdated());
        static::assertEquals($tag_parent, $tag->getParent());
        static::assertEquals($display, $tag->getDisplay());
        static::assertEquals($properties, $tag->getProperties());
        static::assertEquals(null, $tag->getLockTime());

        static::assertEquals('', $tag->getTitle('fr'));
        static::assertEquals('', $tag->getDescription('fr'));

        $titleEs = 'título';
        $titleArray = ['en' => $title, 'es' => $titleEs];
        $descriptionEs = 'descripción';
        $descriptionArray = ['en' => $description, 'es' => $descriptionEs];

        $tag->setI18nTitle($titleArray);
        $tag->setI18nDescription($descriptionArray);

        static::assertEquals($titleArray, $tag->getI18nTitle());
        static::assertEquals($descriptionArray, $tag->getI18nDescription());

        static::assertEquals($tag->getTitle(), $tag->__toString());

        $testProperty = 'test property';
        $tag->setProperty('test', $testProperty);
        static::assertEquals($youtubeProperty, $tag->getProperty('youtube'));
        static::assertEquals($testProperty, $tag->getProperty('test'));

        $testProperty = null;
        $tag->setProperty('test', $testProperty);
        static::assertEquals($testProperty, $tag->getProperty('test'));
    }

    public function testNumberMultimediaObjects()
    {
        $tag = new Tag();
        static::assertEquals(0, $tag->getNumberMultimediaObjects());

        $tag->increaseNumberMultimediaObjects();
        static::assertEquals(1, $tag->getNumberMultimediaObjects());

        $tag->increaseNumberMultimediaObjects();
        static::assertEquals(2, $tag->getNumberMultimediaObjects());

        $tag->decreaseNumberMultimediaObjects();
        static::assertEquals(1, $tag->getNumberMultimediaObjects());

        $tag->decreaseNumberMultimediaObjects();
        static::assertEquals(0, $tag->getNumberMultimediaObjects());

        $count = 5;
        $tag->setNumberMultimediaObjects($count);
        static::assertEquals(5, $tag->getNumberMultimediaObjects());
    }

    public function testChildren()
    {
        $tag_parent = new Tag();
        $tag_child = new Tag();
        $tag_parent->setCod('Parent');
        $tag_child->setCod('ParentChild');
        $tag_grandchild = new Tag();
        $tag_grandchild->setCod('GrandChild');
        $this->dm->persist($tag_parent);
        $this->dm->persist($tag_child);
        $this->dm->persist($tag_grandchild);
        $this->dm->flush();

        static::assertEquals(null, $tag_parent->getParent());
        static::assertFalse($tag_parent->isChildOf($tag_child));
        static::assertFalse($tag_child->isChildOf($tag_child));
        static::assertFalse($tag_parent->isDescendantOf($tag_child));
        static::assertFalse($tag_child->isDescendantOf($tag_child));
        static::assertFalse($tag_parent->isDescendantOfByCod($tag_child->getCod()));
        static::assertFalse($tag_child->isDescendantOfByCod($tag_child->getCod()));

        $tag_child->setParent($tag_parent);
        $tag_grandchild->setParent($tag_child);
        $this->dm->persist($tag_child);
        $this->dm->persist($tag_parent);
        $this->dm->flush();

        static::assertEquals('Parent|ParentChild|GrandChild|', $tag_grandchild->getPath());
        static::assertEquals($tag_parent, $tag_child->getParent());
        static::assertTrue($tag_child->isChildOf($tag_parent));
        static::assertTrue($tag_grandchild->isDescendantOf($tag_parent));
        static::assertTrue($tag_child->isDescendantOfByCod($tag_parent->getCod()));
        static::assertTrue($tag_grandchild->isDescendantOfByCod($tag_parent->getCod()));

        static::assertFalse($tag_grandchild->isChildOf($tag_parent));
        static::assertFalse($tag_parent->isChildOf($tag_child));
        static::assertFalse($tag_parent->isDescendantOf($tag_child));
        static::assertFalse($tag_parent->isDescendantOfByCod($tag_child->getCod()));
    }
}
