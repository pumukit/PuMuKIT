<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class TagRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;

    public function setUp()
    {
        //INIT TEST SUITE
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository(Tag::class);

        //DELETE DATABASE
        $this->dm->getDocumentCollection(Tag::class)->remove([]);
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        gc_collect_cycles();
        parent::tearDown();
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
        $this->assertEquals(1, count($this->repo->findAll()));
    }

    public function testGetChildren()
    {
        $this->createTestTree();

        $tag = $this->repo->findOneByCod('ROOT');
        $tree = $this->repo->getTree($tag);
        $this->assertEquals(6, count($tree));
        $children = $this->repo->getChildren($tag);
        $this->assertEquals(5, count($children));
        $this->assertEquals(5, $this->repo->childCount($tag));
        $directChildren = $this->repo->getChildren($tag, true);
        $this->assertEquals(2, count($directChildren));

        $tag = $this->repo->findOneByCod('B');
        $tree = $this->repo->getTree($tag);
        $this->assertEquals(4, count($tree));
        $children = $this->repo->getChildren($tag);
        $this->assertEquals(3, count($children));
        $this->assertEquals(3, $this->repo->childCount($tag));
        $directChildren = $this->repo->getChildren($tag, true);
        $this->assertEquals(2, count($directChildren));
    }

    public function testGetRootNodes()
    {
        $this->createTestTree();

        $tree = $this->repo->getRootNodes();
        $this->assertEquals(1, count($tree));
    }

    public function testIsChildrenOrDescendantOf()
    {
        $this->createTestTree();

        $root = $this->repo->findOneByCod('ROOT');
        $tagA = $this->repo->findOneByCod('A');
        $tagB = $this->repo->findOneByCod('B');
        $tagB2 = $this->repo->findOneByCod('B2');
        $tagB2A = $this->repo->findOneByCod('B2A');

        $this->assertTrue($tagB2->isChildOf($tagB));
        $this->assertFalse($tagB->isChildOf($tagB2));
        $this->assertFalse($tagB2A->isChildOf($tagB));
        $this->assertFalse($tagA->isChildOf($tagB));
        $this->assertFalse($tagB->isChildOf($tagB));

        $this->assertTrue($tagB2->isDescendantOf($tagB));
        $this->assertFalse($tagB->isDescendantOf($tagB2));
        $this->assertTrue($tagB2A->isDescendantOf($tagB));
        $this->assertTrue($tagB2A->isDescendantOf($tagB2));
        $this->assertTrue($tagB2A->isDescendantOf($root));
        $this->assertFalse($root->isDescendantOf($tagB2A));
        $this->assertFalse($tagA->isDescendantOf($tagB));
        $this->assertFalse($tagB->isDescendantOf($tagB));
    }

    public function testGetChildrenFromDocument()
    {
        $this->createTestTree();
        $this->dm->clear();

        $tag = $this->repo->findOneByCod('ROOT');
        $this->assertEquals(2, count($tag->getChildren()));
    }

    public function testCRUDRepository()
    {
        $this->createTestTree();

        $this->assertEquals(6, count($this->repo->findAll()));

        $tag = $this->repo->findOneByCod('ROOT');
        $tagA = $this->repo->findOneByCod('A');
        $tagB = $this->repo->findOneByCod('B');
        $tagB2A = $this->repo->findOneByCod('B2A');

        //Test rename
        $tag->setCod('ROOT2');
        $this->dm->persist($tag);
        $this->dm->flush();
        $this->assertEquals(4, $tagB2A->getLevel());
        $this->assertEquals(6, count($this->repo->findAll()));

        //Test move
        $tagB->setParent($tagA);
        $this->dm->persist($tag);
        $this->dm->flush();
        $this->assertEquals(5, $tagB2A->getLevel());
        $this->assertEquals(6, count($this->repo->findAll()));

        //Test delete
        $this->dm->remove($tagB);
        $this->dm->flush();
        $this->assertEquals(2, count($this->repo->findAll())); //When a parent is deleted all the descendant.
    }
}
