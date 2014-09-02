<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Tag;

class TagTest extends \PHPUnit_Framework_TestCase
{

	public function testGetterAndSetter()
	{
		$title = 'title';
		$description = 'description';
		$slug = 'slug';
		$left = 5;
		$right = 15;
		$root = 3;
		$level = 2;
		$created = new \DateTime("now");
		$updated = new \DateTime("now");

		$tag = new Tag();

		$tag->setTitle($title);
		$tag->setDescription($description);
		$tag->setSlug($slug);
		$tag->setLeft($left);
		$tag->setRight($right);
		$tag->setRoot($root);
		$tag->setLevel($level);
		$tag->setCreated($created);
		$tag->setUpdated($updated);

		$this->assertEquals($title, $tag->getTitle());
		$this->assertEquals($description, $tag->getDescription());
		$this->assertEquals($slug, $tag->getSlug());
		$this->assertEquals($left, $tag->getLeft());
		$this->assertEquals($right, $tag->getRight());
		$this->assertEquals($root, $tag->getRoot());
		$this->assertEquals($level, $tag->getLevel());
		$this->assertEquals($created, $tag->getCreated());
		$this->assertEquals($updated, $tag->getUpdated());
	}
}
