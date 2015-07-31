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
        $display = true;
        $youtubeProperty = "w7dD-JJJytM&list=PLmXxqSJJq-yUfrjvKe5c5LX_1x7nGVF6c";
        $properties = array('youtube' => $youtubeProperty);

        $tag = new Tag($title);

        $tag->setTitle($title);
        $tag->setDescription($description);
        $tag->setSlug($slug);
        $tag->setCod($cod);
        $tag->setMetatag($metatag);
        $tag->setCreated($created);
        $tag->setUpdated($updated);
        $tag->setDisplay($display);
        $tag->setProperties($properties);

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
        $this->assertEquals($display, $tag->getDisplay());
        $this->assertEquals($properties, $tag->getProperties());
        $this->assertEquals(null, $tag->getLockTime());

        $this->assertEquals('', $tag->getTitle('fr'));
        $this->assertEquals('', $tag->getDescription('fr'));

        $titleEs = 'título';
        $titleArray = array('en' => $title, 'es' => $titleEs);
        $descriptionEs = 'descripción';
        $descriptionArray = array('en' => $description, 'es' => $descriptionEs);

        $tag->setI18nTitle($titleArray);
        $tag->setI18nDescription($descriptionArray);

        $this->assertEquals($titleArray, $tag->getI18nTitle());
        $this->assertEquals($descriptionArray, $tag->getI18nDescription());

        $this->assertEquals($tag->getTitle(), $tag->__toString());

        $testProperty = 'test property';
        $tag->setProperty('test', $testProperty);
        $this->assertEquals($youtubeProperty, $tag->getProperty('youtube'));
        $this->assertEquals($testProperty, $tag->getProperty('test'));

        $testProperty = null;
        $tag->setProperty('test', $testProperty);
        $this->assertEquals($testProperty, $tag->getProperty('test'));
    }

    public function testNumberMultimediaObjects()
    {
        $tag = new Tag();
        $this->assertEquals(0, $tag->getNumberMultimediaObjects());

        $tag->increaseNumberMultimediaObjects();
        $this->assertEquals(1, $tag->getNumberMultimediaObjects());

        $tag->increaseNumberMultimediaObjects();
        $this->assertEquals(2, $tag->getNumberMultimediaObjects());

        $tag->decreaseNumberMultimediaObjects();
        $this->assertEquals(1, $tag->getNumberMultimediaObjects());

        $tag->decreaseNumberMultimediaObjects();
        $this->assertEquals(0, $tag->getNumberMultimediaObjects());

        $count = 5;
        $tag->setNumberMultimediaObjects($count);
        $this->assertEquals(5, $tag->getNumberMultimediaObjects());
    }
}
