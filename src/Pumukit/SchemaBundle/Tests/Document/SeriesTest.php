<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;

class SeriesTest extends \PHPUnit_Framework_TestCase
{
    public function testGetterAndSetter()
    {
        $title = 'title';
        $subtitle = 'subtitle';
        $description = 'description';
        $test_date = new \DateTime("now");
        $serie_type = new SeriesType();

        $serie = new Series();

        $serie->setTitle($title);
        $serie->setSubtitle($subtitle);
        $serie->setDescription($description);
        $serie->setPublicDate($test_date);
        $serie->setSeriesType($serie_type);

        $this->assertEquals($title, $serie->getTitle());
        $this->assertEquals($subtitle, $serie->getSubtitle());
        $this->assertEquals($description, $serie->getDescription());
        $this->assertEquals($test_date, $serie->getPublicDate());
        $this->assertEquals($serie_type, $serie->getSeriesType());
    }

    public function testMultimediaObjectsInSeries()
    {
        $serie = new Series();
        $mm1 = new MultimediaObject();
        $mm2 = new MultimediaObject();
        $mm3 = new MultimediaObject();

        $this->assertEquals(0, count($serie->getMultimediaObjects()));

        $serie->addMultimediaObject($mm1);
        $serie->addMultimediaObject($mm2);
        $serie->addMultimediaObject($mm3);
        $this->assertEquals(3, count($serie->getMultimediaObjects()));

        $serie->removeMultimediaObject($mm2);
        $this->assertEquals(2, count($serie->getMultimediaObjects()));

        $this->assertTrue($serie->containsMultimediaObject($mm1));
        $this->assertFalse($serie->containsMultimediaObject($mm2));
    }

    public function testRankInAddMultimediaObject()
    {
        $serie = new Series();

        $mm1 = new MultimediaObject();
        $mm2 = new MultimediaObject();
        $mm3 = new MultimediaObject();
        $mm4 = new MultimediaObject();
        $mm5 = new MultimediaObject();

        $serie->addMultimediaObject($mm1);
        $serie->addMultimediaObject($mm2);
        $serie->addMultimediaObject($mm3);
        $serie->addMultimediaObject($mm4);
        $serie->addMultimediaObject($mm5);

        $this->assertEquals(1, $mm1->getRank());
        $this->assertEquals(2, $mm2->getRank());
        $this->assertEquals(3, $mm3->getRank());
        $this->assertEquals(4, $mm4->getRank());
        $this->assertEquals(5, $mm5->getRank());
    }

    // TODO
    /*
    public function testGetMultimediaObjectsByTag()
    {
        $s = new Series();

        $tag0 = new Tag();
        $tag1 = new Tag();
        $tag2 = new Tag();
        $tag3 = new Tag();
        $tag4 = new Tag();
        $tag5 = new Tag();
        $tag6 = new Tag();
        $tag7 = new Tag();
        $tag8 = new Tag();

        $mm1 = new MultimediaObject();
        $mm1->setRank(3);
        //$mm1->setTags(array($tag1));
        $mm1->addTag($tag1);

        $mm2 = new MultimediaObject();
        $mm2->setRank(2);
        $mm2->setTags(array($tag2, $tag1, $tag3));
        $mm3 = new MultimediaObject();
        $mm3->setRank(1);
        $mm3->setTags(array($tag1, $tag2));
        $mm4 = new MultimediaObject();
        $mm4->setRank(4);
        $mm4->setTags(array($tag4, $tag5, $tag6));
        $mm5 = new MultimediaObject();
        $mm5->setRank(5);
        $mm5->setTags(array($tag4, $tag7));

        $s->addMultimediaObject($mm3);
        $s->addMultimediaObject($mm2);
        $s->addMultimediaObject($mm1);
        $s->addMultimediaObject($mm4);
        $s->addMultimediaObject($mm5);

        $this->assertEquals(array($mm3, $mm2, $mm1), $s->getMultimediaObjectsByTag($tag1));
        $this->assertEquals($mm3, $s->getMultimediaObjectByTag($tag1));
        $this->assertEquals(null, $s->getMultimediaObjectByTag($tag8));
        $this->assertEquals($mm3, $s->getMultimediaObjectWithAnyTag(array($tag1, $tag8)));
        $this->assertEquals(array($mm2), $s->getMultimediaObjectsWithAllTags(array($tag1, $tag2, $tag3)));
        $this->assertTrue(in_array($s->getMultimediaObjectWithAllTags(array($tag2,$tag1)),array($mm3, $mm2)));
        $this->assertEquals(null, $s->getMultimediaObjectWithAllTags(array($tag2,$tag1,$tag8)));
        $this->assertEquals(4, count($s->getMultimediaObjectsWithAnyTag(array($tag1,$tag7))));
        $this->assertEquals(1, count($s->getMultimediaObjectWithAnyTag(array($tag1))));
        $this->assertEquals(null, $s->getMultimediaObjectWithAnyTag(array($tag8)));

        $this->assertEquals(5, count($s->getFilteredMultimediaObjectsByTags()));
        $this->assertEquals(3, count($s->getFilteredMultimediaObjectsByTags(array($tag1))));
        $this->assertEquals(1, count($s->getFilteredMultimediaObjectsByTags(array($tag1), array($tag2, $tag3))));
        $this->assertEquals(0, count($s->getFilteredMultimediaObjectsByTags(array(), array($tag2, $tag3), array($tag1))));
        $this->assertEquals(3, count($s->getFilteredMultimediaObjectsByTags(array(), array(), array($tag4))));
        $this->assertEquals(0, count($s->getFilteredMultimediaObjectsByTags(array(), array(), array($tag4, $tag1))));
        $this->assertEquals(5, count($s->getFilteredMultimediaObjectsByTags(array(), array(), array(), array($tag4, $tag1))));
        $this->assertEquals(1, count($s->getFilteredMultimediaObjectsByTags(array($tag2, $tag3), array(), array(), array($tag3))));
    }*/
}
