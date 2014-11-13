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
        $cod = 23;
        $metatag = true;
        $created = new \DateTime("now");
        $updated = new \DateTime("now");

        $tag = new Tag($title);

        $tag->setTitle($title);
        $tag->setDescription($description);
        $tag->setSlug($slug);
        $tag->setCod($cod);
        $tag->setMetatag($metatag);
        $tag->setCreated($created);
        $tag->setUpdated($updated);

        $tag_parent = new Tag("parent");
        $tag->setParent($tag_parent);

        $this->assertEquals($title, $tag->getTitle());
        $this->assertEquals($description, $tag->getDescription());
        $this->assertEquals($slug, $tag->getSlug());
        $this->assertEquals($cod, $tag->getCod());
        $this->assertEquals($metatag, $tag->getMetatag());
        $this->assertEquals($created, $tag->getCreated());
        $this->assertEquals($updated, $tag->getUpdated());
        $this->assertEquals($tag_parent, $tag->getParent());
    }
}
