<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Tag;

/**
 * @internal
 * @coversNothing
 */
class TagRepositoryTest extends PumukitTestCase
{
    private $repo;

    public function setUp(): void
    {
        //INIT TEST SUITE
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->repo = $this->dm->getRepository(Tag::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        gc_collect_cycles();
    }

    public function createTestTree()
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
    }

    public function testCreate()
    {
        $tag = new Tag();
        $tag->setCod('ROOT');

        $this->dm->persist($tag);
        $this->dm->flush();

        // This should pass to check the unrequired fields
        static::assertCount(1, $this->repo->findAll());
    }

    public function testGetChildren()
    {
        $this->createTestTree();

        $tag = $this->repo->findOneByCod('ROOT');
        $tree = $this->repo->getTree($tag);
        static::assertCount(6, $tree);
        $children = $this->repo->getChildren($tag);
        static::assertCount(5, $children);
        static::assertEquals(5, $this->repo->childCount($tag));
        $directChildren = $this->repo->getChildren($tag, true);
        static::assertCount(2, $directChildren);

        $tag = $this->repo->findOneByCod('B');
        $tree = $this->repo->getTree($tag);
        static::assertCount(4, $tree);
        $children = $this->repo->getChildren($tag);
        static::assertCount(3, $children);
        static::assertEquals(3, $this->repo->childCount($tag));
        $directChildren = $this->repo->getChildren($tag, true);
        static::assertCount(2, $directChildren);
    }

    public function testGetRootNodes()
    {
        $this->createTestTree();

        $tree = $this->repo->getRootNodes();
        static::assertCount(1, $tree);
    }

    public function testIsChildrenOrDescendantOf()
    {
        $this->createTestTree();

        $root = $this->repo->findOneByCod('ROOT');
        $tagA = $this->repo->findOneByCod('A');
        $tagB = $this->repo->findOneByCod('B');
        $tagB2 = $this->repo->findOneByCod('B2');
        $tagB2A = $this->repo->findOneByCod('B2A');

        static::assertTrue($tagB2->isChildOf($tagB));
        static::assertFalse($tagB->isChildOf($tagB2));
        static::assertFalse($tagB2A->isChildOf($tagB));
        static::assertFalse($tagA->isChildOf($tagB));
        static::assertFalse($tagB->isChildOf($tagB));

        static::assertTrue($tagB2->isDescendantOf($tagB));
        static::assertFalse($tagB->isDescendantOf($tagB2));
        static::assertTrue($tagB2A->isDescendantOf($tagB));
        static::assertTrue($tagB2A->isDescendantOf($tagB2));
        static::assertTrue($tagB2A->isDescendantOf($root));
        static::assertFalse($root->isDescendantOf($tagB2A));
        static::assertFalse($tagA->isDescendantOf($tagB));
        static::assertFalse($tagB->isDescendantOf($tagB));
    }

    public function testGetChildrenFromDocument()
    {
        $this->createTestTree();
        $this->dm->clear();

        $tag = $this->repo->findOneByCod('ROOT');
        static::assertCount(2, $tag->getChildren());
    }

    public function testCRUDRepository()
    {
        $this->createTestTree();

        static::assertCount(6, $this->repo->findAll());

        $tag = $this->repo->findOneByCod('ROOT');
        $tagA = $this->repo->findOneByCod('A');
        $tagB = $this->repo->findOneByCod('B');
        $tagB2A = $this->repo->findOneByCod('B2A');

        //Test rename
        $tag->setCod('ROOT2');
        $this->dm->persist($tag);
        $this->dm->flush();
        static::assertEquals(4, $tagB2A->getLevel());
        static::assertCount(6, $this->repo->findAll());

        //Test move
        $tagB->setParent($tagA);
        $this->dm->persist($tag);
        $this->dm->flush();
        static::assertEquals(5, $tagB2A->getLevel());
        static::assertCount(6, $this->repo->findAll());

        //Test delete
        $this->dm->remove($tagB);
        $this->dm->flush();
        static::assertCount(2, $this->repo->findAll()); //When a parent is deleted all the descendant.
    }
}
