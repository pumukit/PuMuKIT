<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Tag;
use Doctrine\Common\Collections\ArrayCollection;


class TagTest extends \PHPUnit_Framework_TestCase
{

	public function testGetterAndSetter()
	{
		$title = 'title';
		$description = 'description';
		$slug = 'slug';
		$cod = 23;
		$metatag = true;
		$left = 5;
		$right = 15;
		$root = 3;
		$level = 2;
		$created = new \DateTime("now");
		$updated = new \DateTime("now");

		$tag = new Tag($title);

		$tag->setTitle($title);
		$tag->setDescription($description);
		$tag->setSlug($slug);
		$tag->setCod($cod);
		$tag->setMetatag($metatag);
		$tag->setLeft($left);
		$tag->setRight($right);
		$tag->setRoot($root);
		$tag->setLevel($level);
		$tag->setCreated($created);
		$tag->setUpdated($updated);

		$tag_child1 = new Tag("child1");
		$tag->addChildren($tag_child1);
		$tag_child2 = new Tag("child2");
		$tag->addChildren($tag_child2);
		$children_array = [$tag_child1, $tag_child2];
		$children = new ArrayCollection($children_array);

		$tag_parent = new Tag("parent");
		$tag->setParent($tag_parent);

		$this->assertEquals($title, $tag->getTitle());
		$this->assertEquals($description, $tag->getDescription());
		$this->assertEquals($slug, $tag->getSlug());
		$this->assertEquals($cod, $tag->getCod());
		$this->assertEquals($metatag, $tag->getMetatag());
		$this->assertEquals($left, $tag->getLeft());
		$this->assertEquals($right, $tag->getRight());
		$this->assertEquals($root, $tag->getRoot());
		$this->assertEquals($level, $tag->getLevel());
		$this->assertEquals($created, $tag->getCreated());
		$this->assertEquals($updated, $tag->getUpdated());
		$this->assertEquals($children, $tag->getChildren());
		$this->assertEquals($tag_parent, $tag->getParent());
	}
}
