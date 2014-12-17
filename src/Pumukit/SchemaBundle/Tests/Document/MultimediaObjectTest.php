<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\Link;
//use Pumukit\SchemaBundle\Document\PersonInMultimediaObject;

class MultimediaObjectTest extends \PHPUnit_Framework_TestCase
{
    public function testGetterAndSetter()
    {
        $series = new Series();

        $rank = 3;
        $status = MultimediaObject::STATUS_NORMAL;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $title = 'Star Wars';
        $subtitle = 'Spoiler';
        $description = "Darth Vader: Obi-Wan never told you what happened to your father.
			Luke Skywalker: He told me enough! He told me you killed him!
			Darth Vader: No. I am your father.
			Luke Skywalker: No... that's not true! That's impossible!";

        $tag1 = new Tag();
        $tag2 = new Tag();
        $tag3 = new Tag();
        $mm_tags = array($tag1, $tag2, $tag3);

	$broadcast = new Broadcast();
	$broadcast->setName('Private');
	$broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PRI);
	$broadcast->setPasswd('password');
	$broadcast->setDefaultSel(true);
	$broadcast->setDescription('Private broadcast');

        $mm = new MultimediaObject();
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

        $this->assertEquals($series, $mm->getSeries());
        $this->assertEquals($rank, $mm->getRank());
        $this->assertEquals($status, $mm->getStatus());
        $this->assertEquals($record_date, $mm->getRecordDate());
        $this->assertEquals($public_date, $mm->getPublicDate());
        $this->assertEquals($title, $mm->getTitle());
        $this->assertNotEquals($title."2", $mm->getTitle());
        $this->assertEquals($subtitle, $mm->getSubtitle());
        $this->assertEquals($description, $mm->getDescription());
        // TODO $this->assertEquals($mm_tags, $mm->getTags()->toArray());
	$this->assertEquals($broadcast, $mm->getBroadcast());
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

        $mm->removeTrack($track2);
        $this->assertEquals(2, count($mm->getTracks()));

        $this->assertTrue($mm->containsTrack($track1));
        $this->assertFalse($mm->containsTrack($track2));
    }

    public function testPicsInMultimediaObject()
    {
        $mm = new MultimediaObject();
        $pic1 = new Pic();
        $pic2 = new Pic();
        $pic3 = new Pic();

        $this->assertEquals(0, count($mm->getPics()));

        $mm->addPic($pic1);
        $mm->addPic($pic2);
        $mm->addPic($pic3);
        $this->assertEquals(3, count($mm->getPics()));

        $mm->removePic($pic2);
        $this->assertEquals(2, count($mm->getPics()));

        $this->assertTrue($mm->containsPic($pic1));
        $this->assertFalse($mm->containsPic($pic2));
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

        $mm->removeMaterial($material2);
        $this->assertEquals(2, count($mm->getMaterials()));

        $this->assertTrue($mm->containsMaterial($material1));
        $this->assertFalse($mm->containsMaterial($material2));
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

        $mm->removeLink($link2);
        $this->assertEquals(2, count($mm->getLinks()));

        $this->assertTrue($mm->containsLink($link1));
        $this->assertFalse($mm->containsLink($link2));       
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

    public function testGetTracksByTag()
    {
        $mm = new MultimediaObject();

        $t1 = new Track();
        $t1->setTags(array('master'));
        $t2 = new Track();
        $t2->setTags(array('mosca', 'master', 'old'));
        $t3 = new Track();
        $t3->setTags(array('master', 'mosca'));
        $t4 = new Track();
        $t4->setTags(array('flv', 'itunes', 'hide'));
        $t5 = new Track();
        $t5->setTags(array('flv', 'webtv'));

        $mm->addTrack($t3);
        $mm->addTrack($t2);
        $mm->addTrack($t1);
        $mm->addTrack($t4);
        $mm->addTrack($t5);

        $this->assertEquals(array($t3, $t2, $t1), $mm->getTracksByTag('master'));
        $this->assertEquals($t3, $mm->getTrackByTag('master'));
        $this->assertEquals(null, $mm->getTrackByTag('del_universo'));
        $this->assertEquals($t3, $mm->getTrackWithAnyTag(array('master', 'pr')));
        $this->assertEquals(array($t2), $mm->getTracksWithAllTags(array('master', 'mosca', 'old')));
        $this->assertTrue(in_array($mm->getTrackWithAllTags(array('mosca', 'master')), array($t3, $t2)));
        $this->assertEquals(null, $mm->getTrackWithAllTags(array('mosca', 'master', 'del_universo')));
        $this->assertEquals(4, count($mm->getTracksWithAnyTag(array('master', 'webtv'))));
        $this->assertEquals(1, count($mm->getTrackWithAnyTag(array('master'))));
        $this->assertEquals(null, $mm->getTrackWithAnyTag(array('del_universo')));

        $this->assertEquals(5, count($mm->getFilteredTracksByTags()));
        $this->assertEquals(3, count($mm->getFilteredTracksByTags(array('master'))));
        $this->assertEquals(1, count($mm->getFilteredTracksByTags(array('master'), array('mosca', 'old'))));
        $this->assertEquals(0, count($mm->getFilteredTracksByTags(array(), array('mosca', 'old'), array('master'))));
        $this->assertEquals(3, count($mm->getFilteredTracksByTags(array(), array(), array('flv'))));
        $this->assertEquals(0, count($mm->getFilteredTracksByTags(array(), array(), array('flv', 'master'))));
        $this->assertEquals(5, count($mm->getFilteredTracksByTags(array(), array(), array(), array('flv', 'master'))));
        $this->assertEquals(1, count($mm->getFilteredTracksByTags(array('mosca', 'old'), array(), array(), array('old'))));
    }

    public function testGetPicsByTag()
    {
        $mm = new MultimediaObject();

        $p1 = new Pic();
        $p1->setTags(array('master'));
        $p2 = new Pic();
        $p2->setTags(array('master', 'mosca', 'old'));
        $p3 = new Pic();
        $p3->setTags(array('master', 'mosca'));
        $p4 = new Pic();
        $p4->setTags(array('flv', 'itunes', 'hide'));
        $p5 = new Pic();
        $p5->setTags(array('flv', 'webtv'));

        $mm->addPic($p3);
        $mm->addPic($p2);
        $mm->addPic($p1);
        $mm->addPic($p4);
        $mm->addPic($p5);

        $this->assertEquals(array($p3, $p2, $p1), $mm->getPicsByTag('master'));
        $this->assertEquals($p3, $mm->getPicByTag('master'));
        $this->assertEquals(null, $mm->getPicByTag('del_universo'));
        $this->assertEquals($p3, $mm->getPicWithAnyTag(array('master', 'pr')));
        $this->assertEquals(array($p2), $mm->getPicsWithAllTags(array('master', 'mosca', 'old')));
        $this->assertTrue(in_array($mm->getPicWithAllTags(array('mosca', 'master')), array($p3, $p2)));
        $this->assertEquals(null, $mm->getPicWithAllTags(array('mosca', 'master', 'del_universo')));
        $this->assertEquals(4, count($mm->getPicsWithAnyTag(array('master', 'webtv'))));
        $this->assertEquals(1, count($mm->getPicWithAnyTag(array('master'))));
        $this->assertEquals(null, $mm->getPicWithAnyTag(array('del_universo')));

        $this->assertEquals(5, count($mm->getFilteredPicsByTags()));
        $this->assertEquals(3, count($mm->getFilteredPicsByTags(array('master'))));
        $this->assertEquals(1, count($mm->getFilteredPicsByTags(array('master'), array('mosca', 'old'))));
        $this->assertEquals(0, count($mm->getFilteredPicsByTags(array(), array('mosca', 'old'), array('master'))));
        $this->assertEquals(3, count($mm->getFilteredPicsByTags(array(), array(), array('flv'))));
        $this->assertEquals(0, count($mm->getFilteredPicsByTags(array(), array(), array('flv', 'master'))));
        $this->assertEquals(5, count($mm->getFilteredPicsByTags(array(), array(), array(), array('flv', 'master'))));
        $this->assertEquals(1, count($mm->getFilteredPicsByTags(array('mosca', 'old'), array(), array(), array('old'))));
    }

    public function testGetMaterialsByTag()
    {
        $mm = new MultimediaObject();

        $m1 = new Material();
        $m1->setTags(array('master'));
        $m2 = new Material();
        $m2->setTags(array('mosca', 'master', 'old'));
        $m3 = new Material();
        $m3->setTags(array('master', 'mosca'));
        $m4 = new Material();
        $m4->setTags(array('flv', 'itunes', 'hide'));
        $m5 = new Material();
        $m5->setTags(array('flv', 'webtv'));

        $mm->addMaterial($m3);
        $mm->addMaterial($m2);
        $mm->addMaterial($m1);
        $mm->addMaterial($m4);
        $mm->addMaterial($m5);

        $this->assertEquals(array($m3, $m2, $m1), $mm->getMaterialsByTag('master'));
        $this->assertEquals($m3, $mm->getMaterialByTag('master'));
        $this->assertEquals(null, $mm->getMaterialByTag('del_universo'));
        $this->assertEquals($m3, $mm->getMaterialWithAnyTag(array('master', 'pr')));
        $this->assertEquals(array($m2), $mm->getMaterialsWithAllTags(array('master', 'mosca', 'old')));
        $this->assertTrue(in_array($mm->getMaterialWithAllTags(array('mosca', 'master')), array($m3, $m2)));
        $this->assertEquals(null, $mm->getMaterialWithAllTags(array('mosca', 'master', 'del_universo')));
        $this->assertEquals(4, count($mm->getMaterialsWithAnyTag(array('master', 'webtv'))));
        $this->assertEquals(1, count($mm->getMaterialWithAnyTag(array('master'))));
        $this->assertEquals(null, $mm->getMaterialWithAnyTag(array('del_universo')));

        $this->assertEquals(5, count($mm->getFilteredMaterialsByTags()));
        $this->assertEquals(3, count($mm->getFilteredMaterialsByTags(array('master'))));
        $this->assertEquals(1, count($mm->getFilteredMaterialsByTags(array('master'), array('mosca', 'old'))));
        $this->assertEquals(0, count($mm->getFilteredMaterialsByTags(array(), array('mosca', 'old'), array('master'))));
        $this->assertEquals(3, count($mm->getFilteredMaterialsByTags(array(), array(), array('flv'))));
        $this->assertEquals(0, count($mm->getFilteredMaterialsByTags(array(), array(), array('flv', 'master'))));
        $this->assertEquals(5, count($mm->getFilteredMaterialsByTags(array(), array(), array(), array('flv', 'master'))));
        $this->assertEquals(1, count($mm->getFilteredMaterialsByTags(array('mosca', 'old'), array(), array(), array('old'))));
    }

    public function testEmbeddedTag()
    {
        $locale = 'en';
	$title = 'title';
        $description = 'description';
        $slug = 'slug';
        $cod = 23;
        $metatag = true;
        $created = new \DateTime("now");
        $updated = new \DateTime("now");
	$display = true;

        $tag = new Tag($title);

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
	$titleArray = array('en' => $title, 'es' => $titleEs);
	$descriptionEs = 'descripción';
	$descriptionArray = array('en' => $description, 'es' => $descriptionEs);

	$tag->setI18nTitle($titleArray);
	$tag->setI18nDescription($descriptionArray);
	
        $mm = new MultimediaObject();

	$mm->addTag($tag);

	// TEST GETTERS

        $this->assertEquals($locale, $mm->getTags()[0]->getLocale());
        $this->assertEquals($title, $mm->getTags()[0]->getTitle());
        $this->assertEquals($description, $mm->getTags()[0]->getDescription());
        $this->assertEquals($slug, $mm->getTags()[0]->getSlug());
        $this->assertEquals($cod,  $mm->getTags()[0]->getCod());
        $this->assertEquals($metatag, $mm->getTags()[0]->getMetatag());
        $this->assertEquals($created, $mm->getTags()[0]->getCreated());
        $this->assertEquals($updated, $mm->getTags()[0]->getUpdated());
	$this->assertEquals($display, $mm->getTags()[0]->getDisplay());
	$this->assertEquals($tag->getPath(), $mm->getTags()[0]->getPath());
	$this->assertEquals($tag->getLevel(), $mm->getTags()[0]->getLevel());
	
	$this->assertNull($mm->getTags()[0]->getTitle('fr'));
	$this->assertNull($mm->getTags()[0]->getDescription('fr'));

	$this->assertEquals($titleArray, $mm->getTags()[0]->getI18nTitle());
	$this->assertEquals($descriptionArray, $mm->getTags()[0]->getI18nDescription());

	$this->assertEquals($mm->getTags()[0]->getTitle(), $mm->getTags()[0]->__toString());

	// TEST SETTERS
	
	$title = 'modified title';
        $description = 'modified description';
        $slug = 'modified slug';
        $cod = 'modcod';
        $metatag = false;
        $created = new \DateTime("now");
        $updated = new \DateTime("now");
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
	$titleArray = array('en' => $title, 'es' => $titleEs);
	$descriptionEs = 'descripción modificada';
	$descriptionArray = array('en' => $description, 'es' => $descriptionEs);

	$mm->getTags()[0]->setI18nTitle($titleArray);
	$mm->getTags()[0]->setI18nDescription($descriptionArray);

        $this->assertEquals($title, $mm->getTags()[0]->getTitle());
        $this->assertEquals($description, $mm->getTags()[0]->getDescription());
        $this->assertEquals($slug, $mm->getTags()[0]->getSlug());
        $this->assertEquals($cod,  $mm->getTags()[0]->getCod());
        $this->assertEquals($metatag, $mm->getTags()[0]->getMetatag());
        $this->assertEquals($created, $mm->getTags()[0]->getCreated());
        $this->assertEquals($updated, $mm->getTags()[0]->getUpdated());
	$this->assertEquals($display, $mm->getTags()[0]->getDisplay());
	
	$this->assertNull($mm->getTags()[0]->getTitle('fr'));
	$this->assertNull($mm->getTags()[0]->getDescription('fr'));

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
        $this->assertTrue($mm->containsAnyTag(array($tag0, $tag2)));
        $this->assertTrue($mm->containsAnyTag(array($tag2, $tag3)));
        $this->assertFalse($mm->containsAnyTag(array($tag0, $tag4)));
        $this->assertTrue($mm->containsAllTags(array($tag1, $tag2)));
        $this->assertTrue($mm->containsAllTags(array($tag1)));
        $this->assertFalse($mm->containsAllTags(array($tag0, $tag2)));
        $this->assertFalse($mm->containsAllTags(array($tag0, $tag1, $tag2, $tag3)));
    }

    /*public function testPersonInMultimediaObject()
    {
        $mm = new MultimediaObject();
        $pimo_sky = new PersonInMultimediaObject();
        $pimo_lucy = new PersonInMultimediaObject();
        $pimo_sky = new PersonInMultimediaObject();
        // $person_humana = new Person();
        // $person_riesgo = new Person();

        $person_pepe = new Person();
        $person_lucy = new Person();
        $person_sky = new Person();
        $person_diamonds = new Person();
        $role_tank = new Role();
        $role_dps = new Role();
        $role_unused = new Role();

        $person_pepe->setName("Pepe");
        $person_lucy->setName("Lucy");
        $person_sky->setname("Sky");

        $role_tank->setName("Tank");
        $role_dps->setName("DPS");
        $role_unused->setName("Unused");

        $this->assertFalse($mm->containsPersonWithRole($person_pepe));
        $this->assertFalse($mm->containsPersonWithRole($person_pepe, $role_tank));
        $this->assertEquals(0, count($mm->getPeopleInMultimediaObject()));

        $mm->addPersonWithRole($person_pepe, $role_tank);
        $this->assertTrue($mm->containsPersonWithRole($person_pepe));
        $this->assertTrue($mm->containsPersonWithRole($person_pepe, $role_tank));

        $this->assertFalse($mm->containsPersonWithRole($person_pepe, $role_dps));
        $this->assertFalse($mm->containsPersonWithRole($person_lucy, $role_tank));
        $this->assertFalse($mm->containsPersonWithRole($person_lucy));

        // Repeat pimo with same person and role , remove the same pimo twice.
        $mm->addPersonWithRole($person_pepe, $role_tank);
        $this->assertEquals(1, count($mm->getPeopleInMultimediaObject()));
        $this->assertTrue($mm->removePersonWithRole($person_pepe, $role_tank));
        $this->assertEquals(0, count($mm->getPeopleInMultimediaObject()));
        $this->assertFalse($mm->removePersonWithRole($person_pepe, $role_tank));

        // Check different people with different roles

        $mm->addPersonWithRole($person_pepe, $role_tank);
        $mm->addPersonWithRole($person_lucy, $role_dps);
        $mm->addPersonWithRole($person_sky, $role_dps);

        // PhpUnit no acepta estos métodos:

        // $pimo_pepe->setMultimediaObject($mm);
        // $pimo_pepe->setPerson($person_pepe);
        // $pimo_pepe->setRole($role_tank);
        // $pimo_pepe->setRank(0);

        // $pimo_lucy->setMultimediaObject($mm);
        // $pimo_lucy->setPerson($person_lucy);
        // $pimo_lucy->setRole($role_dps);
        // $pimo_lucy->setRank(1);

        // $pimo_sky->setMultimediaObject($mm);
        // $pimo_sky->setPerson($person_sky);
        // $pimo_sky->setRole($role_dps);
        // $pimo_sky->setRank(2);

        $three_results = array ();
        $three_results = $mm->getPeopleInMultimediaObjectByRole();

        $this->assertEquals(3,count($three_results));
        $this->assertEquals($three_results[0]->getPerson(), $person_pepe);
        $this->assertEquals($three_results[0]->getRank(), 0);
        $this->assertNotEquals($three_results[0]->getRole(), $role_unused);
        $this->assertEquals($three_results[2]->getPerson(), $person_sky);
        $this->assertEquals($three_results[2]->getRole(), $role_dps);
        $this->assertEquals($three_results[2]->getRank(), 2);

        $two_results = array ();
        $two_results = $mm->getPeopleInMultimediaObjectByRole($role_dps);

        $this->assertEquals(2,count($two_results));
        $this->assertEquals($two_results[0]->getPerson(), $person_lucy);
        $this->assertNotEquals($two_results[0]->getRole(), $role_unused);
        $this->assertEquals($two_results[1]->getPerson(), $person_sky);
        $this->assertEquals($two_results[1]->getRole(), $role_dps);

        // Three results should be equal to this, but mm->pimo->rank swapping is not implemented
        $this->assertFalse($mm->removePersonWithRole($person_pepe, $role_dps));
        $this->assertTrue($mm->removePersonWithRole($person_pepe, $role_tank));
        $this->assertEquals(2,count($mm->getPeopleInMultimediaObjectByRole()));

        // TO DO in middle term - implement sortable (swap, etc) behavior in databases.
        // Review lib/behavior/SortableBehavior.class.php in pumukit - symfony 1

        // TO DO: Ejemplos parecidos para implementar

        // $this->assertEquals(array($mm3, $mm2, $mm1), $s->getMultimediaObjectsByTag($tag1));
        // $this->assertEquals($mm3, $s->getMultimediaObjectByTag($tag1));
        // $this->assertEquals(NULL, $s->getMultimediaObjectByTag($tag8));
        // $this->assertEquals($mm3, $s->getMultimediaObjectWithAnyTag(array($tag1, $tag8)));
        // $this->assertEquals(array($mm2), $s->getMultimediaObjectsWithAllTags(array($tag1, $tag2, $tag3)));
        // $this->assertTrue(in_array($s->getMultimediaObjectWithAllTags(array($tag2,$tag1)),array($mm3, $mm2)));
        // $this->assertEquals(NULL, $s->getMultimediaObjectWithAllTags(array($tag2,$tag1,$tag8)));
        // $this->assertEquals(4, count($s->getMultimediaObjectsWithAnyTag(array($tag1,$tag7))));
        // $this->assertEquals(1, count($s->getMultimediaObjectWithAnyTag(array($tag1))));
        // $this->assertEquals(NULL, $s->getMultimediaObjectWithAnyTag(array($tag8)));

        // $this->assertEquals(5, count($s->getFilteredMultimediaObjectsByTags()));
        // $this->assertEquals(3, count($s->getFilteredMultimediaObjectsByTags(array($tag1))));
        // $this->assertEquals(1, count($s->getFilteredMultimediaObjectsByTags(array($tag1), array($tag2, $tag3))));
        // $this->assertEquals(0, count($s->getFilteredMultimediaObjectsByTags(array(), array($tag2, $tag3), array($tag1))));
        // $this->assertEquals(3, count($s->getFilteredMultimediaObjectsByTags(array(), array(), array($tag4))));
        // $this->assertEquals(0, count($s->getFilteredMultimediaObjectsByTags(array(), array(), array($tag4, $tag1))));
        // $this->assertEquals(5, count($s->getFilteredMultimediaObjectsByTags(array(), array(), array(), array($tag4, $tag1))));
        // $this->assertEquals(1, count($s->getFilteredMultimediaObjectsByTags(array($tag2, $tag3), array(), array(), array($tag3))))

    }*/
}
