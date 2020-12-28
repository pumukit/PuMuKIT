<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Link;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
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

        $mm = new MultimediaObject();
        static::assertInstanceOf('DateTime', $mm->getPropertyAsDateTime('created'));
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
        $mm->setNumview($numview);
        $mm->setLocale($locale);
        $mm->setLine2($line2);
        $mm->addKeyword($keyword, $locale);
        $mm->setProperties($properties);

        static::assertEquals($series, $mm->getSeries());
        static::assertEquals($rank, $mm->getRank());
        static::assertEquals($status, $mm->getStatus());
        static::assertEquals($record_date, $mm->getRecordDate());
        static::assertEquals($public_date, $mm->getPublicDate());
        static::assertEquals($title, $mm->getTitle());
        static::assertNotEquals($title.'2', $mm->getTitle());
        static::assertEquals($subtitle, $mm->getSubtitle());
        static::assertEquals($description, $mm->getDescription());
        static::assertCount(count($mm_tags), $mm->getTags());
        static::assertEquals($numview, $mm->getNumview());
        static::assertEquals($locale, $mm->getLocale());
        static::assertEquals($line2, $mm->getLine2());
        static::assertEquals([$keyword], $mm->getKeywords($locale));
        static::assertEquals($properties, $mm->getProperties());

        $title = null;
        $subtitle = null;
        $description = null;
        $line2 = null;
        $keyword = 'keyword';

        $mm->setTitle($title);
        $mm->setSubtitle($subtitle);
        $mm->setDescription($description);
        $mm->setLine2($line2);
        $mm->addKeyword($keyword);

        static::assertEquals(null, $mm->getTitle());
        static::assertEquals(null, $mm->getSubtitle());
        static::assertEquals(null, $mm->getDescription());
        static::assertEquals(null, $mm->getLine2());
        static::assertEquals([$keyword, $keyword], $mm->getKeywords());

        $properties = ['prop2' => 'property2'];
        $mm->setProperties($properties);
        $key = 'prop2';

        static::assertEquals($properties[$key], $mm->getProperty($key));

        $property3 = 'property3';
        $value3 = 'value3';
        $mm->setProperty($property3, $value3);

        static::assertEquals($value3, $mm->getProperty($property3));

        $mm->removeProperty($property3);

        static::assertNull($mm->getProperty($property3));
    }

    public function testPropertiesDateTime()
    {
        $series = new Series();
        $date = new \DateTime();

        $series->setPropertyAsDateTime('test_date', $date);
        static::assertEquals($date->getTimestamp(), $series->getPropertyAsDateTime('test_date')->getTimestamp());
    }

    public function testToString()
    {
        $mm = new MultimediaObject();
        static::assertEquals($mm->getTitle(), $mm->__toString());
    }

    public function testDefaultState()
    {
        $mm = new MultimediaObject();
        static::assertEquals(MultimediaObject::STATUS_NEW, $mm->getStatus());
    }

    public function testTracksInMultimediaObject()
    {
        $mm = new MultimediaObject();
        $track1 = new Track();
        $track2 = new Track();
        $track3 = new Track();

        static::assertCount(0, $mm->getTracks());

        $mm->addTrack($track1);
        $mm->addTrack($track2);
        $mm->addTrack($track3);
        static::assertCount(3, $mm->getTracks());

        $tracksArray = [$track1, $track2, $track3];
        static::assertEquals($tracksArray, $mm->getTracks()->toArray());

        $mm->removeTrack($track2);
        static::assertCount(2, $mm->getTracks());

        static::assertTrue($mm->containsTrack($track1));
        static::assertFalse($mm->containsTrack($track2));

        $tracksArray = [$track1, $track3];
        static::assertEquals($tracksArray, array_values($mm->getTracks()->toArray()));
    }

    public function testPicsInMultimediaObject()
    {
        $mm = new MultimediaObject();
        $pic1 = new Pic();
        $pic2 = new Pic();
        $pic3 = new Pic();
        $pic4 = new Pic();

        $pic4->setUrl('/url/of/pic4');

        static::assertCount(0, $mm->getPics());

        $mm->addPic($pic1);
        $mm->addPic($pic2);
        $mm->addPic($pic3);
        static::assertCount(3, $mm->getPics());

        $picsArray = [$pic1, $pic2, $pic3];
        static::assertEquals($picsArray, $mm->getPics()->toArray());

        $mm->removePic($pic2);
        static::assertCount(2, $mm->getPics());

        static::assertTrue($mm->containsPic($pic1));
        static::assertFalse($mm->containsPic($pic2));

        $picsArray = [$pic1, $pic3];
        static::assertEquals($picsArray, array_values($mm->getPics()->toArray()));
        static::assertEquals($pic1, $mm->getPic());

        $mm->addPic($pic4);
        static::assertEquals('/url/of/pic4', $mm->getFirstUrlPic());
    }

    public function testMaterialsInMultimediaObject()
    {
        $mm = new MultimediaObject();
        $material1 = new Material();
        $material2 = new Material();
        $material3 = new Material();

        static::assertCount(0, $mm->getMaterials());

        $mm->addMaterial($material1);
        $mm->addMaterial($material2);
        $mm->addMaterial($material3);
        static::assertCount(3, $mm->getMaterials());

        $materialsArray = [$material1, $material2, $material3];
        static::assertEquals($materialsArray, $mm->getMaterials()->toArray());

        $mm->removeMaterial($material2);
        static::assertCount(2, $mm->getMaterials());

        static::assertTrue($mm->containsMaterial($material1));
        static::assertFalse($mm->containsMaterial($material2));

        $materialsArray = [$material1, $material3];
        static::assertEquals($materialsArray, array_values($mm->getMaterials()->toArray()));
    }

    public function testLinksInMultimediaObject()
    {
        $mm = new MultimediaObject();
        $link1 = new Link();
        $link2 = new Link();
        $link3 = new Link();

        static::assertCount(0, $mm->getLinks());

        $mm->addLink($link1);
        $mm->addLink($link2);
        $mm->addLink($link3);
        static::assertCount(3, $mm->getLinks());

        $linksArray = [$link1, $link2, $link3];
        static::assertEquals($linksArray, $mm->getLinks()->toArray());

        $mm->removeLink($link2);
        static::assertCount(2, $mm->getLinks());

        static::assertTrue($mm->containsLink($link1));
        static::assertFalse($mm->containsLink($link2));

        $linksArray = [$link1, $link3];
        static::assertEquals($linksArray, array_values($mm->getLinks()->toArray()));
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

        static::assertEquals(0, $mm->getDuration());
        $mm->addTrack($t1);
        static::assertEquals(2, $mm->getDuration());
        $mm->addTrack($t2);
        static::assertEquals(3, $mm->getDuration());
        $mm->addTrack($t3);
        static::assertEquals(3, $mm->getDuration());
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

        static::assertEquals([$t3, $t2, $t1], $mm->getTracksWithTag('master'));
        static::assertEquals($t3, $mm->getTrackWithTag('master'));
        static::assertEquals(null, $mm->getTrackWithTag('del_universo'));
        static::assertEquals($t3, $mm->getTrackWithAnyTag(['master', 'pr']));
        static::assertEquals([$t2], $mm->getTracksWithAllTags(['master', 'mosca', 'old']));
        static::assertContains($mm->getTrackWithAllTags(['mosca', 'master']), [$t3, $t2]);
        static::assertEquals(null, $mm->getTrackWithAllTags(['mosca', 'master', 'del_universo']));
        static::assertCount(4, $mm->getTracksWithAnyTag(['master', 'webtv']));
        static::assertEquals($t3, $mm->getTrackWithAnyTag(['master']));
        static::assertEquals(null, $mm->getTrackWithAnyTag(['del_universo']));

        static::assertCount(5, $mm->getFilteredTracksWithTags());
        static::assertCount(3, $mm->getFilteredTracksWithTags(['master']));
        static::assertCount(1, $mm->getFilteredTracksWithTags(['master'], ['mosca', 'old']));
        static::assertCount(0, $mm->getFilteredTracksWithTags([], ['mosca', 'old'], ['master']));
        static::assertCount(3, $mm->getFilteredTracksWithTags([], [], ['flv']));
        static::assertCount(0, $mm->getFilteredTracksWithTags([], [], ['flv', 'master']));
        static::assertCount(5, $mm->getFilteredTracksWithTags([], [], [], ['flv', 'master']));
        static::assertCount(1, $mm->getFilteredTracksWithTags(['mosca', 'old'], [], [], ['old']));

        static::assertEquals($t3, $mm->getFilteredTrackWithTags());
        static::assertEquals($t3, $mm->getFilteredTrackWithTags(['master']));
        static::assertEquals($t2, $mm->getFilteredTrackWithTags(['master'], ['mosca', 'old']));
        static::assertEquals(null, $mm->getFilteredTrackWithTags([], ['mosca', 'old'], ['master']));
        static::assertEquals($t3, $mm->getFilteredTrackWithTags([], [], ['flv']));
        static::assertEquals(null, $mm->getFilteredTrackWithTags([], [], ['flv', 'master']));
        static::assertEquals($t3, $mm->getFilteredTrackWithTags([], [], [], ['flv', 'master']));
        static::assertEquals($t3, $mm->getFilteredTrackWithTags(['mosca', 'old'], [], [], ['old']));
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

        static::assertEquals([$p3, $p2, $p1], $mm->getPicsWithTag('master'));
        static::assertEquals($p3, $mm->getPicWithTag('master'));
        static::assertEquals(null, $mm->getPicWithTag('del_universo'));
        static::assertEquals($p3, $mm->getPicWithAnyTag(['master', 'pr']));
        static::assertEquals([$p2], $mm->getPicsWithAllTags(['master', 'mosca', 'old']));
        static::assertContains($mm->getPicWithAllTags(['mosca', 'master']), [$p3, $p2]);
        static::assertEquals(null, $mm->getPicWithAllTags(['mosca', 'master', 'del_universo']));
        static::assertCount(4, $mm->getPicsWithAnyTag(['master', 'webtv']));
        static::assertEquals($p3, $mm->getPicWithAnyTag(['master']));
        static::assertEquals(null, $mm->getPicWithAnyTag(['del_universo']));

        static::assertCount(5, $mm->getFilteredPicsWithTags());
        static::assertCount(3, $mm->getFilteredPicsWithTags(['master']));
        static::assertCount(1, $mm->getFilteredPicsWithTags(['master'], ['mosca', 'old']));
        static::assertCount(0, $mm->getFilteredPicsWithTags([], ['mosca', 'old'], ['master']));
        static::assertCount(3, $mm->getFilteredPicsWithTags([], [], ['flv']));
        static::assertCount(0, $mm->getFilteredPicsWithTags([], [], ['flv', 'master']));
        static::assertCount(5, $mm->getFilteredPicsWithTags([], [], [], ['flv', 'master']));
        static::assertCount(1, $mm->getFilteredPicsWithTags(['mosca', 'old'], [], [], ['old']));
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

        static::assertEquals([$m3, $m2, $m1], $mm->getMaterialsWithTag('master'));
        static::assertEquals($m3, $mm->getMaterialWithTag('master'));
        static::assertEquals(null, $mm->getMaterialWithTag('del_universo'));
        static::assertEquals($m3, $mm->getMaterialWithAnyTag(['master', 'pr']));
        static::assertEquals([$m2], $mm->getMaterialsWithAllTags(['master', 'mosca', 'old']));
        static::assertContains($mm->getMaterialWithAllTags(['mosca', 'master']), [$m3, $m2]);
        static::assertEquals(null, $mm->getMaterialWithAllTags(['mosca', 'master', 'del_universo']));
        static::assertCount(4, $mm->getMaterialsWithAnyTag(['master', 'webtv']));
        static::assertEquals($m3, $mm->getMaterialWithAnyTag(['master']));
        static::assertEquals(null, $mm->getMaterialWithAnyTag(['del_universo']));

        static::assertCount(5, $mm->getFilteredMaterialsWithTags());
        static::assertCount(3, $mm->getFilteredMaterialsWithTags(['master']));
        static::assertCount(1, $mm->getFilteredMaterialsWithTags(['master'], ['mosca', 'old']));
        static::assertCount(0, $mm->getFilteredMaterialsWithTags([], ['mosca', 'old'], ['master']));
        static::assertCount(3, $mm->getFilteredMaterialsWithTags([], [], ['flv']));
        static::assertCount(0, $mm->getFilteredMaterialsWithTags([], [], ['flv', 'master']));
        static::assertCount(5, $mm->getFilteredMaterialsWithTags([], [], [], ['flv', 'master']));
        static::assertCount(1, $mm->getFilteredMaterialsWithTags(['mosca', 'old'], [], [], ['old']));
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

        static::assertEquals([$m3, $m2, $m1], $mm->getLinksWithTag('master'));
        static::assertEquals($m3, $mm->getLinkWithTag('master'));
        static::assertEquals(null, $mm->getLinkWithTag('del_universo'));
        static::assertEquals($m3, $mm->getLinkWithAnyTag(['master', 'pr']));
        static::assertEquals([$m2], $mm->getLinksWithAllTags(['master', 'mosca', 'old']));
        static::assertContains($mm->getLinkWithAllTags(['mosca', 'master']), [$m3, $m2]);
        static::assertEquals(null, $mm->getLinkWithAllTags(['mosca', 'master', 'del_universo']));
        static::assertCount(4, $mm->getLinksWithAnyTag(['master', 'webtv']));
        static::assertEquals($m3, $mm->getLinkWithAnyTag(['master']));
        static::assertEquals(null, $mm->getLinkWithAnyTag(['del_universo']));

        static::assertCount(5, $mm->getFilteredLinksWithTags());
        static::assertCount(3, $mm->getFilteredLinksWithTags(['master']));
        static::assertCount(1, $mm->getFilteredLinksWithTags(['master'], ['mosca', 'old']));
        static::assertCount(0, $mm->getFilteredLinksWithTags([], ['mosca', 'old'], ['master']));
        static::assertCount(3, $mm->getFilteredLinksWithTags([], [], ['flv']));
        static::assertCount(0, $mm->getFilteredLinksWithTags([], [], ['flv', 'master']));
        static::assertCount(5, $mm->getFilteredLinksWithTags([], [], [], ['flv', 'master']));
        static::assertCount(1, $mm->getFilteredLinksWithTags(['mosca', 'old'], [], [], ['old']));
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

        static::assertEquals($locale, $mm->getTags()[0]->getLocale());
        static::assertEquals($title, $mm->getTags()[0]->getTitle());
        static::assertEquals($description, $mm->getTags()[0]->getDescription());
        static::assertEquals($slug, $mm->getTags()[0]->getSlug());
        static::assertEquals($cod, $mm->getTags()[0]->getCod());
        static::assertEquals($metatag, $mm->getTags()[0]->getMetatag());
        static::assertEquals($created, $mm->getTags()[0]->getCreated());
        static::assertEquals($updated, $mm->getTags()[0]->getUpdated());
        static::assertEquals($display, $mm->getTags()[0]->getDisplay());
        static::assertEquals($tag->getPath(), $mm->getTags()[0]->getPath());
        static::assertEquals($tag->getLevel(), $mm->getTags()[0]->getLevel());

        static::assertEquals('', $mm->getTags()[0]->getTitle('fr'));
        static::assertEquals('', $mm->getTags()[0]->getDescription('fr'));

        static::assertEquals($titleArray, $mm->getTags()[0]->getI18nTitle());
        static::assertEquals($descriptionArray, $mm->getTags()[0]->getI18nDescription());

        static::assertEquals($mm->getTags()[0]->getTitle(), $mm->getTags()[0]->__toString());

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

        static::assertEquals($title, $mm->getTags()[0]->getTitle());
        static::assertEquals($description, $mm->getTags()[0]->getDescription());
        static::assertEquals($slug, $mm->getTags()[0]->getSlug());
        static::assertEquals($cod, $mm->getTags()[0]->getCod());
        static::assertEquals($metatag, $mm->getTags()[0]->getMetatag());
        static::assertEquals($created, $mm->getTags()[0]->getCreated());
        static::assertEquals($updated, $mm->getTags()[0]->getUpdated());
        static::assertEquals($display, $mm->getTags()[0]->getDisplay());

        static::assertEquals('', $mm->getTags()[0]->getTitle('fr'));
        static::assertEquals('', $mm->getTags()[0]->getDescription('fr'));

        static::assertEquals($titleArray, $mm->getTags()[0]->getI18nTitle());
        static::assertEquals($descriptionArray, $mm->getTags()[0]->getI18nDescription());

        static::assertEquals($mm->getTags()[0]->getTitle(), $mm->getTags()[0]->__toString());

        $locale = 'es';
        $mm->getTags()[0]->setLocale($locale);
        static::assertEquals($titleEs, $mm->getTags()[0]->getTitle());
        static::assertEquals($descriptionEs, $mm->getTags()[0]->getDescription());
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

        static::assertFalse($mm->containsTag($tag1));
        $mm->addTag($tag1);
        static::assertTrue($mm->containsTag($tag1));
        $mm->removeTag($tag1);
        static::assertFalse($mm->containsTag($tag1));

        static::assertFalse($mm->containsTagWithCod($tag1->getCod()));
        $mm->addTag($tag1);
        static::assertTrue($mm->containsTagWithCod($tag1->getCod()));
        $mm->removeTag($tag1);
        static::assertFalse($mm->containsTagWithCod($tag1->getCod()));

        //Repeat Tag
        static::assertFalse($mm->containsTag($tag1));
        $mm->addTag($tag1);
        $mm->addTag($tag1);
        static::assertTrue($mm->containsTag($tag1));
        $mm->removeTag($tag1);
        static::assertFalse($mm->containsTag($tag1));
        static::assertFalse($mm->removeTag($tag1));

        //containsAllTag and containsAnyTag
        $mm->addTag($tag1);
        $mm->addTag($tag2);
        $mm->addTag($tag3);
        static::assertTrue($mm->containsAnyTag([$tag0, $tag2]));
        static::assertTrue($mm->containsAnyTag([$tag2, $tag3]));
        static::assertFalse($mm->containsAnyTag([$tag0, $tag4]));
        static::assertTrue($mm->containsAllTags([$tag1, $tag2]));
        static::assertTrue($mm->containsAllTags([$tag1]));
        static::assertFalse($mm->containsAllTags([$tag0, $tag2]));
        static::assertFalse($mm->containsAllTags([$tag0, $tag1, $tag2, $tag3]));

        //containsAllTagsWithCodes and containsAnyTagWithCodes
        $mm->removeTag($tag1);
        $mm->removeTag($tag2);
        $mm->removeTag($tag3);
        $mm->addTag($tag1);
        $mm->addTag($tag4);
        static::assertTrue($mm->containsAllTagsWithCodes([$tag1->getCod(), $tag4->getCod()]));
        $mm->removeTag($tag4);
        static::assertFalse($mm->containsAllTagsWithCodes([$tag4->getCod()]));
        static::assertTrue($mm->containsAllTagsWithCodes([$tag1->getCod()]));
        static::assertFalse($mm->containsAnyTagWithCodes([$tag4->getCod()]));
        static::assertTrue($mm->containsAnyTagWithCodes([$tag1->getCod()]));
    }

    public function testIsCollection()
    {
        $mm = new MultimediaObject();
        static::assertEquals(false, $mm->isCollection());
    }

    public function testGetDurationString()
    {
        $duration1 = 120;
        $duration2 = -6;
        $duration3 = 30;

        $mm = new MultimediaObject();

        $mm->setDuration($duration2);
        static::assertEquals("0''", $mm->getDurationString());
        $mm->setDuration($duration1);
        static::assertEquals("2' 00''", $mm->getDurationString());
        $mm->setDuration($duration3);
        static::assertEquals("0' 30''", $mm->getDurationString());
    }

    public function testIncNumview()
    {
        $mm = new MultimediaObject();

        $mm->setNumview(5);
        $mm->incNumview();

        static::assertEquals(6, $mm->getNumview());
    }

    public function testDurationInMinutesAndSeconds()
    {
        $duration = 120;
        $duration_in_minutes_and_seconds1 = ['minutes' => 2, 'seconds' => 0];
        $duration_in_minutes_and_seconds2 = ['minutes' => 5, 'seconds' => 30];

        $mm = new MultimediaObject();
        $mm->setDuration($duration);

        static::assertEquals($duration_in_minutes_and_seconds1, $mm->getDurationInMinutesAndSeconds());

        $mm->setDurationInMinutesAndSeconds($duration_in_minutes_and_seconds2);
        static::assertEquals($duration_in_minutes_and_seconds2, $mm->getDurationInMinutesAndSeconds());
    }
}
