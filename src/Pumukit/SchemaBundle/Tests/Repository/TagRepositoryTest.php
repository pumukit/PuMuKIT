<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Pumukit\SchemaBundle\Document\Tag;

class TagRepositoryTest extends WebTestCase
{

    private $dm;
    private $repo;

    public function setUp()
    {
        //INIT TEST SUITE
	$options = array(
		'environment' => 'test'
	);
        $kernel = static::createKernel($options);
	//$kernel = static::createKernel();
        $kernel->boot();
        $this->dm = $kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository('PumukitSchemaBundle:Tag');

        //DELETE DATABASE
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Tag')->remove(array());
        $this->dm->flush();
    }

    public function testRepository()
    {
        $cod = 123;

        $tag = new Tag();
        $tag->setCod("ROOT");

        $this->dm->persist($tag);
        $this->dm->flush();

        // This should pass to check the unrequired fields
        $this->assertEquals(1, count($this->repo->findAll()));

        $tagA = new Tag();
        $tagA->setCod("A");
        $tagA->setParent($tag);
        $this->dm->persist($tagA);

        $tagB = new Tag();
        $tagB->setCod("B");
        $tagB->setParent($tag);
        $this->dm->persist($tagB);

        $tagB1 = new Tag();
        $tagB1->setCod("B1");
        $tagB1->setParent($tagB);
        $this->dm->persist($tagB1);

        $tagB2 = new Tag();
        $tagB2->setCod("B2");
        $tagB2->setParent($tagB);
        $this->dm->persist($tagB2);

        $tagB2A = new Tag();
        $tagB2A->setCod("B2A");
        $tagB2A->setParent($tagB2);
        $this->dm->persist($tagB2A);

        $this->dm->flush();

        $this->assertEquals(6, count($this->repo->findAll()));

	//Test rename
        $tag->setCod("ROOT2");
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
	

	/**
	   TODO:
	    - isEqualTo
	    - isChildOf
	    - isDescendantOf
	    - getNumberOfChildren
	    - getNumberOfDescendants
	    - getChildren
	    - getDescendants
	    - getPath
	 */
    }
}
