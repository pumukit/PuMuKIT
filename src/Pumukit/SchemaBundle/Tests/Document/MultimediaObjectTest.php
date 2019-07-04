<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\Link;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
//use Pumukit\SchemaBundle\Document\PersonInMultimediaObject;

use Pumukit\SchemaBundle\Document\Track;

/**
 * @internal
 * @coversNothing
 */
class MultimediaObjectTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $series = new Series();

        $rank = 3;
        $status = MultimediaObject::STATUS_PUBLISHED;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $title = 'Star Wars';
        $subtitle = 'Spoiler';
        $description = "Darth Vader: Obi-Wan never told you what happened to your father.
            Luke Skywalker: He told me enough! He told me you killed him!
            Darth Vader: No. I am your father.
            Luke Skywalker: No... that's not true! That's impossible!";
        $numview = 2;
        $locale = 'en';
        $line2 = 'line2';
        $keyword = 'keyword';
        $properties = ['property1', 'property2'];

        $tag1 = new Tag();
        $tag1->setCod('tag1');
        $tag2 = new Tag();
        $tag2->setCod('tag2');
        $tag3 = new Tag();
        $tag3->setCod('tag3');
        $mm_tags = [$tag1, $tag2, $tag3];

        $broadcast = new Broadcast();
        $broadcast->setName('Private');
        $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PRI);
        $broadcast->setPasswd('password');
        $broadcast->setDefaultSel(true);
        $broadcast->setDescription('Private broadcast');

        $mm = new MultimediaObject();
        $this->assertInstanceOf('DateTime', $mm->getPropertyAsDateTime('created'));
        $mm->setRank($rank);
        $mm->setStatus($status);
        $mm->setSeries($series);
        $mm->setRecordDate($record_date);
        $mm->setPublicDate($public_date);
        $mm->setTitle($title);
        $mm->setSubtitle($subtitle);
        $mm->setDescription($description);
        $mm->addTag($tag1);
        $mm->addTag($tag2);
        $mm->addTag($tag3);
        $mm->setBroadcast($broadcast);
        $mm->setNumview($numview);
        $mm->setLocale($locale);
        $mm->setLine2($line2);
        $mm->setKeyword($keyword);
        $mm->setProperties($properties);

        $this->assertEquals($series, $mm->getSeries());
        $this->assertEquals($rank, $mm->getRank());
        $this->assertEquals($status, $mm->getStatus());
        $this->assertEquals($record_date, $mm->getRecordDate());
        $this->assertEquals($public_date, $mm->getPublicDate());
        $this->assertEquals($title, $mm->getTitle());
        $this->assertNotEquals($title.'2', $mm->getTitle());
        $this->assertEquals($subtitle, $mm->getSubtitle());
        $this->assertEquals($description, $mm->getDescription());
        $this->assertEquals(count($mm_tags), count($mm->getTags()));
        $this->assertEquals($broadcast, $mm->getBroadcast());
        $this->assertEquals($numview, $mm->getNumview());
        $this->assertEquals($locale, $mm->getLocale());
        $this->assertEquals($line2, $mm->getLine2());
        $this->assertEquals($keyword, $mm->getKeyword());
        $this->assertEquals($properties, $mm->getProperties());

        $title = null;
        $subtitle = null;
        $description = null;
        $line2 = null;
        $keyword = null;

        $mm->setTitle($title);
        $mm->setSubtitle($subtitle);
        $mm->setDescription($description);
        $mm->setLine2($line2);
        $mm->setKeyword($keyword);

        $this->assertEquals(null, $mm->getTitle());
        $this->assertEquals(null, $mm->getSubtitle());
        $this->assertEquals(null, $mm->getDescription());
        $this->assertEquals(null, $mm->getLine2());
        $this->assertEquals(null, $mm->getKeyword());

        $properties = ['prop2' => 'property2'];
        $mm->setProperties($properties);
        $key = 'prop2';

        $this->assertEquals($properties[$key], $mm->getProperty($key));

        $property3 = 'property3';
        $value3 = 'value3';
        $mm->setProperty($property3, $value3);

        $this->assertEquals($value3, $mm->getProperty($property3));

        $mm->removeProperty($property3);

        $this->assertNull($mm->getProperty($property3));
    }

    public function testPropertiesDateTime()
    {
        $series = new Series();
        $date = new \DateTime();

        $series->setPropertyAsDateTime('test_date', $date);
        $this->assertEquals($date->getTimestamp(), $series->getPropertyAsDateTime('test_date')->getTimestamp());
    }

    public function testToString()
    {
        $mm = new MultimediaObject();
        $this->assertEquals($mm->getTitle(), $mm->__toString());
    }

    public function testDefaultState()
    {
        $mm = new MultimediaObject();
        $this->assertEquals(MultimediaObject::STATUS_NEW, $mm->getStatus());
    }

    public function testTracksInMultimediaObject()
    {
        $mm = new MultimediaObject();
        $track1 = new Track();
        $track2 = new Track();
        $track3 = new Track();

        $this->assertEquals(0, count($mm->getTracks()));

        $mm->addTrack($track1);
        $mm->addTrack($track2);
        $mm->addTrack($track3);
        $this->assertEquals(3, count($mm->getTracks()));

        $tracksArray = [$track1, $track2, $track3];
        $this->assertEquals($tracksArray, $mm->getTracks()->toArray());

        $mm->removeTrack($track2);
        $this->assertEquals(2, count($mm->getTracks()));

        $this->assertTrue($mm->containsTrack($track1));
        $this->assertFalse($mm->containsTrack($track2));

        $tracksArray = [$track1, $track3];
        $this->assertEquals($tracksArray, array_values($mm->getTracks()->toArray()));
    }

    public function testPicsInMultimediaObject()
    {
        $mm = new MultimediaObject();
        $pic1 = new Pic();
        $pic2 = new Pic();
        $pic3 = new Pic();
        $pic4 = new Pic();

        $pic4->setUrl('/url/of/pic4');

        $this->assertEquals(0, count($mm->getPics()));

        $mm->addPic($pic1);
        $mm->addPic($pic2);
        $mm->addPic($pic3);
        $this->assertEquals(3, count($mm->getPics()));

        $picsArray = [$pic1, $pic2, $pic3];
        $this->assertEquals($picsArray, $mm->getPics()->toArray());

        $mm->removePic($pic2);
        $this->assertEquals(2, count($mm->getPics()));

        $this->assertTrue($mm->containsPic($pic1));
        $this->assertFalse($mm->containsPic($pic2));

        $picsArray = [$pic1, $pic3];
        $this->assertEquals($picsArray, array_values($mm->getPics()->toArray()));
        $this->assertEquals($pic1, $mm->getPic());

        $mm->addPic($pic4);
        $this->assertEquals('/url/of/pic4', $mm->getFirstUrlPic());
    }

    public function testMaterialsInMultimediaObject()
    {
        $mm = new MultimediaObject();
        $material1 = new Material();
        $material2 = new Material();
        $material3 = new Material();

        $this->assertEquals(0, count($mm->getMaterials()));

        $mm->addMaterial($material1);
        $mm->addMaterial($material2);
        $mm->addMaterial($material3);
        $this->assertEquals(3, count($mm->getMaterials()));

        $materialsArray = [$material1, $material2, $material3];
        $this->assertEquals($materialsArray, $mm->getMaterials()->toArray());

        $mm->removeMaterial($material2);
        $this->assertEquals(2, count($mm->getMaterials()));

        $this->assertTrue($mm->containsMaterial($material1));
        $this->assertFalse($mm->containsMaterial($material2));

        $materialsArray = [$material1, $material3];
        $this->assertEquals($materialsArray, array_values($mm->getMaterials()->toArray()));
    }

    public function testLinksInMultimediaObject()
    {
        $mm = new MultimediaObject();
        $link1 = new Link();
        $link2 = new Link();
        $link3 = new Link();

        $this->assertEquals(0, count($mm->getLinks()));

        $mm->addLink($link1);
        $mm->addLink($link2);
        $mm->addLink($link3);
        $this->assertEquals(3, count($mm->getLinks()));

        $linksArray = [$link1, $link2, $link3];
        $this->assertEquals($linksArray, $mm->getLinks()->toArray());

        $mm->removeLink($link2);
        $this->assertEquals(2, count($mm->getLinks()));

        $this->assertTrue($mm->containsLink($link1));
        $this->assertFalse($mm->containsLink($link2));

        $linksArray = [$link1, $link3];
        $this->assertEquals($linksArray, array_values($mm->getLinks()->toArray()));
    }

    public function testUpdateMmDurationWhenAddTracks()
    {
        $mm = new MultimediaObject();

        $t1 = new Track();
        $t1->setDuration(2);
        $t2 = new Track();
        $t2->setDuration(3);
        $t3 = new Track();
        $t3->setDuration(1);

        $this->assertEquals(0, $mm->getDuration());
        $mm->addTrack($t1);
        $this->assertEquals(2, $mm->getDuration());
        $mm->addTrack($t2);
        $this->assertEquals(3, $mm->getDuration());
        $mm->addTrack($t3);
        $this->assertEquals(3, $mm->getDuration());
    }

    public function testGetTracksWithTag()
    {
        $mm = new MultimediaObject();

        $t1 = new Track();
        $t1->setTags(['master']);
        $t2 = new Track();
        $t2->setTags(['mosca', 'master', 'old']);
        $t3 = new Track();
        $t3->setTags(['master', 'mosca']);
        $t4 = new Track();
        $t4->setTags(['flv', 'itunes', 'hide']);
        $t5 = new Track();
        $t5->setTags(['flv', 'webtv']);

        $mm->addTrack($t3);
        $mm->addTrack($t2);
        $mm->addTrack($t1);
        $mm->addTrack($t4);
        $mm->addTrack($t5);

        $this->assertEquals([$t3, $t2, $t1], $mm->getTracksWithTag('master'));
        $this->assertEquals($t3, $mm->getTrackWithTag('master'));
        $this->assertEquals(null, $mm->getTrackWithTag('del_universo'));
        $this->assertEquals($t3, $mm->getTrackWithAnyTag(['master', 'pr']));
        $this->assertEquals([$t2], $mm->getTracksWithAllTags(['master', 'mosca', 'old']));
        $this->assertTrue(in_array($mm->getTrackWithAllTags(['mosca', 'master']), [$t3, $t2]));
        $this->assertEquals(null, $mm->getTrackWithAllTags(['mosca', 'master', 'del_universo']));
        $this->assertEquals(4, count($mm->getTracksWithAnyTag(['master', 'webtv'])));
        $this->assertEquals($t3, $mm->getTrackWithAnyTag(['master']));
        $this->assertEquals(null, $mm->getTrackWithAnyTag(['del_universo']));

        $this->assertEquals(5, count($mm->getFilteredTracksWithTags()));
        $this->assertEquals(3, count($mm->getFilteredTracksWithTags(['master'])));
        $this->assertEquals(1, count($mm->getFilteredTracksWithTags(['master'], ['mosca', 'old'])));
        $this->assertEquals(0, count($mm->getFilteredTracksWithTags([], ['mosca', 'old'], ['master'])));
        $this->assertEquals(3, count($mm->getFilteredTracksWithTags([], [], ['flv'])));
        $this->assertEquals(0, count($mm->getFilteredTracksWithTags([], [], ['flv', 'master'])));
        $this->assertEquals(5, count($mm->getFilteredTracksWithTags([], [], [], ['flv', 'master'])));
        $this->assertEquals(1, count($mm->getFilteredTracksWithTags(['mosca', 'old'], [], [], ['old'])));

        $this->assertEquals($t3, $mm->getFilteredTrackWithTags());
        $this->assertEquals($t3, $mm->getFilteredTrackWithTags(['master']));
        $this->assertEquals($t2, $mm->getFilteredTrackWithTags(['master'], ['mosca', 'old']));
        $this->assertEquals(null, $mm->getFilteredTrackWithTags([], ['mosca', 'old'], ['master']));
        $this->assertEquals($t3, $mm->getFilteredTrackWithTags([], [], ['flv']));
        $this->assertEquals(null, $mm->getFilteredTrackWithTags([], [], ['flv', 'master']));
        $this->assertEquals($t3, $mm->getFilteredTrackWithTags([], [], [], ['flv', 'master']));
        $this->assertEquals($t3, $mm->getFilteredTrackWithTags(['mosca', 'old'], [], [], ['old']));
    }

    public function testGetPicsWithTag()
    {
        $mm = new MultimediaObject();

        $p1 = new Pic();
        $p1->setTags(['master']);
        $p2 = new Pic();
        $p2->setTags(['master', 'mosca', 'old']);
        $p3 = new Pic();
        $p3->setTags(['master', 'mosca']);
        $p4 = new Pic();
        $p4->setTags(['flv', 'itunes', 'hide']);
        $p5 = new Pic();
        $p5->setTags(['flv', 'webtv']);

        $mm->addPic($p3);
        $mm->addPic($p2);
        $mm->addPic($p1);
        $mm->addPic($p4);
        $mm->addPic($p5);

        $this->assertEquals([$p3, $p2, $p1], $mm->getPicsWithTag('master'));
        $this->assertEquals($p3, $mm->getPicWithTag('master'));
        $this->assertEquals(null, $mm->getPicWithTag('del_universo'));
        $this->assertEquals($p3, $mm->getPicWithAnyTag(['master', 'pr']));
        $this->assertEquals([$p2], $mm->getPicsWithAllTags(['master', 'mosca', 'old']));
        $this->assertTrue(in_array($mm->getPicWithAllTags(['mosca', 'master']), [$p3, $p2]));
        $this->assertEquals(null, $mm->getPicWithAllTags(['mosca', 'master', 'del_universo']));
        $this->assertEquals(4, count($mm->getPicsWithAnyTag(['master', 'webtv'])));
        $this->assertEquals($p3, $mm->getPicWithAnyTag(['master']));
        $this->assertEquals(null, $mm->getPicWithAnyTag(['del_universo']));

        $this->assertEquals(5, count($mm->getFilteredPicsWithTags()));
        $this->assertEquals(3, count($mm->getFilteredPicsWithTags(['master'])));
        $this->assertEquals(1, count($mm->getFilteredPicsWithTags(['master'], ['mosca', 'old'])));
        $this->assertEquals(0, count($mm->getFilteredPicsWithTags([], ['mosca', 'old'], ['master'])));
        $this->assertEquals(3, count($mm->getFilteredPicsWithTags([], [], ['flv'])));
        $this->assertEquals(0, count($mm->getFilteredPicsWithTags([], [], ['flv', 'master'])));
        $this->assertEquals(5, count($mm->getFilteredPicsWithTags([], [], [], ['flv', 'master'])));
        $this->assertEquals(1, count($mm->getFilteredPicsWithTags(['mosca', 'old'], [], [], ['old'])));
    }

    public function testGetMaterialsWithTag()
    {
        $mm = new MultimediaObject();

        $m1 = new Material();
        $m1->setTags(['master']);
        $m2 = new Material();
        $m2->setTags(['mosca', 'master', 'old']);
        $m3 = new Material();
        $m3->setTags(['master', 'mosca']);
        $m4 = new Material();
        $m4->setTags(['flv', 'itunes', 'hide']);
        $m5 = new Material();
        $m5->setTags(['flv', 'webtv']);

        $mm->addMaterial($m3);
        $mm->addMaterial($m2);
        $mm->addMaterial($m1);
        $mm->addMaterial($m4);
        $mm->addMaterial($m5);

        $this->assertEquals([$m3, $m2, $m1], $mm->getMaterialsWithTag('master'));
        $this->assertEquals($m3, $mm->getMaterialWithTag('master'));
        $this->assertEquals(null, $mm->getMaterialWithTag('del_universo'));
        $this->assertEquals($m3, $mm->getMaterialWithAnyTag(['master', 'pr']));
        $this->assertEquals([$m2], $mm->getMaterialsWithAllTags(['master', 'mosca', 'old']));
        $this->assertTrue(in_array($mm->getMaterialWithAllTags(['mosca', 'master']), [$m3, $m2]));
        $this->assertEquals(null, $mm->getMaterialWithAllTags(['mosca', 'master', 'del_universo']));
        $this->assertEquals(4, count($mm->getMaterialsWithAnyTag(['master', 'webtv'])));
        $this->assertEquals($m3, $mm->getMaterialWithAnyTag(['master']));
        $this->assertEquals(null, $mm->getMaterialWithAnyTag(['del_universo']));

        $this->assertEquals(5, count($mm->getFilteredMaterialsWithTags()));
        $this->assertEquals(3, count($mm->getFilteredMaterialsWithTags(['master'])));
        $this->assertEquals(1, count($mm->getFilteredMaterialsWithTags(['master'], ['mosca', 'old'])));
        $this->assertEquals(0, count($mm->getFilteredMaterialsWithTags([], ['mosca', 'old'], ['master'])));
        $this->assertEquals(3, count($mm->getFilteredMaterialsWithTags([], [], ['flv'])));
        $this->assertEquals(0, count($mm->getFilteredMaterialsWithTags([], [], ['flv', 'master'])));
        $this->assertEquals(5, count($mm->getFilteredMaterialsWithTags([], [], [], ['flv', 'master'])));
        $this->assertEquals(1, count($mm->getFilteredMaterialsWithTags(['mosca', 'old'], [], [], ['old'])));
    }

    public function testGetLinksWithTag()
    {
        $mm = new MultimediaObject();

        $m1 = new Link();
        $m1->setTags(['master']);
        $m2 = new Link();
        $m2->setTags(['mosca', 'master', 'old']);
        $m3 = new Link();
        $m3->setTags(['master', 'mosca']);
        $m4 = new Link();
        $m4->setTags(['flv', 'itunes', 'hide']);
        $m5 = new Link();
        $m5->setTags(['flv', 'webtv']);

        $mm->addLink($m3);
        $mm->addLink($m2);
        $mm->addLink($m1);
        $mm->addLink($m4);
        $mm->addLink($m5);

        $this->assertEquals([$m3, $m2, $m1], $mm->getLinksWithTag('master'));
        $this->assertEquals($m3, $mm->getLinkWithTag('master'));
        $this->assertEquals(null, $mm->getLinkWithTag('del_universo'));
        $this->assertEquals($m3, $mm->getLinkWithAnyTag(['master', 'pr']));
        $this->assertEquals([$m2], $mm->getLinksWithAllTags(['master', 'mosca', 'old']));
        $this->assertTrue(in_array($mm->getLinkWithAllTags(['mosca', 'master']), [$m3, $m2]));
        $this->assertEquals(null, $mm->getLinkWithAllTags(['mosca', 'master', 'del_universo']));
        $this->assertEquals(4, count($mm->getLinksWithAnyTag(['master', 'webtv'])));
        $this->assertEquals($m3, $mm->getLinkWithAnyTag(['master']));
        $this->assertEquals(null, $mm->getLinkWithAnyTag(['del_universo']));

        $this->assertEquals(5, count($mm->getFilteredLinksWithTags()));
        $this->assertEquals(3, count($mm->getFilteredLinksWithTags(['master'])));
        $this->assertEquals(1, count($mm->getFilteredLinksWithTags(['master'], ['mosca', 'old'])));
        $this->assertEquals(0, count($mm->getFilteredLinksWithTags([], ['mosca', 'old'], ['master'])));
        $this->assertEquals(3, count($mm->getFilteredLinksWithTags([], [], ['flv'])));
        $this->assertEquals(0, count($mm->getFilteredLinksWithTags([], [], ['flv', 'master'])));
        $this->assertEquals(5, count($mm->getFilteredLinksWithTags([], [], [], ['flv', 'master'])));
        $this->assertEquals(1, count($mm->getFilteredLinksWithTags(['mosca', 'old'], [], [], ['old'])));
    }

    public function testEmbeddedTag()
    {
        $locale = 'en';
        $title = 'title';
        $description = 'description';
        $slug = 'slug';
        $cod = 23;
        $metatag = true;
        $created = new \DateTime('now');
        $updated = new \DateTime('now');
        $display = true;

        $tag = new Tag();

        $tag->setLocale($locale);
        $tag->setTitle($title);
        $tag->setDescription($description);
        $tag->setSlug($slug);
        $tag->setCod($cod);
        $tag->setMetatag($metatag);
        $tag->setCreated($created);
        $tag->setUpdated($updated);
        $tag->setDisplay($display);

        $titleEs = 'título';
        $titleArray = ['en' => $title, 'es' => $titleEs];
        $descriptionEs = 'descripción';
        $descriptionArray = ['en' => $description, 'es' => $descriptionEs];

        $tag->setI18nTitle($titleArray);
        $tag->setI18nDescription($descriptionArray);

        $mm = new MultimediaObject();

        $mm->addTag($tag);

        // TEST GETTERS

        $this->assertEquals($locale, $mm->getTags()[0]->getLocale());
        $this->assertEquals($title, $mm->getTags()[0]->getTitle());
        $this->assertEquals($description, $mm->getTags()[0]->getDescription());
        $this->assertEquals($slug, $mm->getTags()[0]->getSlug());
        $this->assertEquals($cod, $mm->getTags()[0]->getCod());
        $this->assertEquals($metatag, $mm->getTags()[0]->getMetatag());
        $this->assertEquals($created, $mm->getTags()[0]->getCreated());
        $this->assertEquals($updated, $mm->getTags()[0]->getUpdated());
        $this->assertEquals($display, $mm->getTags()[0]->getDisplay());
        $this->assertEquals($tag->getPath(), $mm->getTags()[0]->getPath());
        $this->assertEquals($tag->getLevel(), $mm->getTags()[0]->getLevel());

        $this->assertEquals('', $mm->getTags()[0]->getTitle('fr'));
        $this->assertEquals('', $mm->getTags()[0]->getDescription('fr'));

        $this->assertEquals($titleArray, $mm->getTags()[0]->getI18nTitle());
        $this->assertEquals($descriptionArray, $mm->getTags()[0]->getI18nDescription());

        $this->assertEquals($mm->getTags()[0]->getTitle(), $mm->getTags()[0]->__toString());

        // TEST SETTERS

        $title = 'modified title';
        $description = 'modified description';
        $slug = 'modified slug';
        $cod = 'modcod';
        $metatag = false;
        $created = new \DateTime('now');
        $updated = new \DateTime('now');
        $display = false;

        $mm->getTags()[0]->setTitle($title);
        $mm->getTags()[0]->setDescription($description);
        $mm->getTags()[0]->setSlug($slug);
        $mm->getTags()[0]->setCod($cod);
        $mm->getTags()[0]->setMetatag($metatag);
        $mm->getTags()[0]->setCreated($created);
        $mm->getTags()[0]->setUpdated($updated);
        $mm->getTags()[0]->setDisplay($display);

        $titleEs = 'título modificado';
        $titleArray = ['en' => $title, 'es' => $titleEs];
        $descriptionEs = 'descripción modificada';
        $descriptionArray = ['en' => $description, 'es' => $descriptionEs];

        $mm->getTags()[0]->setI18nTitle($titleArray);
        $mm->getTags()[0]->setI18nDescription($descriptionArray);

        $this->assertEquals($title, $mm->getTags()[0]->getTitle());
        $this->assertEquals($description, $mm->getTags()[0]->getDescription());
        $this->assertEquals($slug, $mm->getTags()[0]->getSlug());
        $this->assertEquals($cod, $mm->getTags()[0]->getCod());
        $this->assertEquals($metatag, $mm->getTags()[0]->getMetatag());
        $this->assertEquals($created, $mm->getTags()[0]->getCreated());
        $this->assertEquals($updated, $mm->getTags()[0]->getUpdated());
        $this->assertEquals($display, $mm->getTags()[0]->getDisplay());

        $this->assertEquals('', $mm->getTags()[0]->getTitle('fr'));
        $this->assertEquals('', $mm->getTags()[0]->getDescription('fr'));

        $this->assertEquals($titleArray, $mm->getTags()[0]->getI18nTitle());
        $this->assertEquals($descriptionArray, $mm->getTags()[0]->getI18nDescription());

        $this->assertEquals($mm->getTags()[0]->getTitle(), $mm->getTags()[0]->__toString());

        $locale = 'es';
        $mm->getTags()[0]->setLocale($locale);
        $this->assertEquals($titleEs, $mm->getTags()[0]->getTitle());
        $this->assertEquals($descriptionEs, $mm->getTags()[0]->getDescription());
    }

    public function testTagCollection()
    {
        $mm = new MultimediaObject();

        $tag0 = new Tag();
        $tag0->setCod('cod0');
        $tag1 = new Tag();
        $tag1->setCod('cod1');
        $tag2 = new Tag();
        $tag2->setCod('cod2');
        $tag3 = new Tag();
        $tag3->setCod('cod3');
        $tag4 = new Tag();
        $tag4->setCod('cod4');

        $this->assertFalse($mm->containsTag($tag1));
        $mm->addTag($tag1);
        $this->assertTrue($mm->containsTag($tag1));
        $mm->removeTag($tag1);
        $this->assertFalse($mm->containsTag($tag1));

        $this->assertFalse($mm->containsTagWithCod($tag1->getCod()));
        $mm->addTag($tag1);
        $this->assertTrue($mm->containsTagWithCod($tag1->getCod()));
        $mm->removeTag($tag1);
        $this->assertFalse($mm->containsTagWithCod($tag1->getCod()));

        //Repeat Tag
        $this->assertFalse($mm->containsTag($tag1));
        $mm->addTag($tag1);
        $mm->addTag($tag1);
        $this->assertTrue($mm->containsTag($tag1));
        $mm->removeTag($tag1);
        $this->assertFalse($mm->containsTag($tag1));
        $this->assertFalse($mm->removeTag($tag1));

        //containsAllTag and containsAnyTag
        $mm->addTag($tag1);
        $mm->addTag($tag2);
        $mm->addTag($tag3);
        $this->assertTrue($mm->containsAnyTag([$tag0, $tag2]));
        $this->assertTrue($mm->containsAnyTag([$tag2, $tag3]));
        $this->assertFalse($mm->containsAnyTag([$tag0, $tag4]));
        $this->assertTrue($mm->containsAllTags([$tag1, $tag2]));
        $this->assertTrue($mm->containsAllTags([$tag1]));
        $this->assertFalse($mm->containsAllTags([$tag0, $tag2]));
        $this->assertFalse($mm->containsAllTags([$tag0, $tag1, $tag2, $tag3]));

        //containsAllTagsWithCodes and containsAnyTagWithCodes
        $mm->removeTag($tag1);
        $mm->removeTag($tag2);
        $mm->removeTag($tag3);
        $mm->addTag($tag1);
        $mm->addTag($tag4);
        $this->assertTrue($mm->containsAllTagsWithCodes([$tag1->getCod(), $tag4->getCod()]));
        $mm->removeTag($tag4);
        $this->assertFalse($mm->containsAllTagsWithCodes([$tag4->getCod()]));
        $this->assertTrue($mm->containsAllTagsWithCodes([$tag1->getCod()]));
        $this->assertFalse($mm->containsAnyTagWithCodes([$tag4->getCod()]));
        $this->assertTrue($mm->containsAnyTagWithCodes([$tag1->getCod()]));
    }

    public function testIsCollection()
    {
        $mm = new MultimediaObject();
        $this->assertEquals(false, $mm->isCollection());
    }

    public function testGetDurationString()
    {
        $duration1 = 120;
        $duration2 = -6;
        $duration3 = 30;

        $mm = new MultimediaObject();

        $mm->setDuration($duration2);
        $this->assertEquals("0''", $mm->getDurationString());
        $mm->setDuration($duration1);
        $this->assertEquals("2' 00''", $mm->getDurationString());
        $mm->setDuration($duration3);
        $this->assertEquals("30''", $mm->getDurationString());
    }

    public function testIncNumview()
    {
        $mm = new MultimediaObject();

        $mm->setNumview(5);
        $mm->incNumview();

        $this->assertEquals(6, $mm->getNumview());
    }

    public function testDurationInMinutesAndSeconds()
    {
        $duration = 120;
        $duration_in_minutes_and_seconds1 = ['minutes' => 2, 'seconds' => 0];
        $duration_in_minutes_and_seconds2 = ['minutes' => 5, 'seconds' => 30];

        $mm = new MultimediaObject();
        $mm->setDuration($duration);

        $this->assertEquals($duration_in_minutes_and_seconds1, $mm->getDurationInMinutesAndSeconds());

        $mm->setDurationInMinutesAndSeconds($duration_in_minutes_and_seconds2);
        $this->assertEquals($duration_in_minutes_and_seconds2, $mm->getDurationInMinutesAndSeconds());
    }
}
