<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\PersonInMultimediaObject;
use Pumukit\SchemaBundle\Document\SeriesType;

class MultimediaObjectRepositoryTest extends WebTestCase
{
	private $dm;
	private $repo;
	private $qb;

	public function setUp()
	{
		$options = array('environment' => 'test');
		$kernel = static::createKernel($options);
		$kernel->boot();
		$this->dm = $kernel->getContainer()
			->get('doctrine_mongodb')->getManager();
		$this->repo = $this->dm
			->getRepository('PumukitSchemaBundle:MultimediaObject');

		//DELETE DATABASE
		// pimo has to be deleted before mmobj
		$this->dm->getDocumentCollection('PumukitSchemaBundle:PersonInMultimediaObject')
			->remove(array());
		$this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')
			->remove(array());
		$this->dm->getDocumentCollection('PumukitSchemaBundle:Role')
			->remove(array());
		$this->dm->getDocumentCollection('PumukitSchemaBundle:Person')
			->remove(array());
		$this->dm->getDocumentCollection('PumukitSchemaBundle:Series')
			->remove(array());	
		$this->dm->getDocumentCollection('PumukitSchemaBundle:SeriesType')
			->remove(array());
		$this->dm->flush();
	}

	public function testRepositoryEmpty()
	{
		$this->assertEquals(0, count($this->repo->findAll()));
	}

	public function testRepository()
	{
		$rank = 1;
		$status = MultimediaObject::STATUS_NORMAL;
		$record_date = new \DateTime();
		$public_date = new \DateTime();
		$title = 'titulo cualquiera';
		$subtitle = 'Subtitle paragraph';
		$description = "Description text";
		$duration = 300;

		$mmobj = new MultimediaObject();
		$mmobj->setRank($rank);
		$mmobj->setStatus($status);
		$mmobj->setRecordDate($record_date);
		$mmobj->setPublicDate($public_date);
		$mmobj->setTitle($title);
		$mmobj->setSubtitle($subtitle);
		$mmobj->setDescription($description);
		$mmobj->setDuration($duration);

		$this->dm->persist($mmobj);
		$this->dm->flush();

		$this->assertEquals(1, count($this->repo->findAll()));

	}

	public function testCreateMultimediaObjectAndFindByCriteria()
	{
		$series_type = $this->createSeriesType("Medieval Fantasy Sitcom");

		$series_main = $this->createSeries("Stark's growing pains");
		$series_wall = $this->createSeries("The Wall");
		$series_lhazar = $this->createSeries("A quiet life");

		$person_ned = $this->createPerson('Ned');
		$person_benjen = $this->createPerson('Benjen');	

		$role_lord = $this->createRole("Lord");
		$role_ranger = $this->createRole("First Ranger");
		$role_hand = $this->createRole("Hand of the King");

		$series_type->addSeries($series_main);

		$mm1 = $this->createMultimediaObjectAssignedToSeries('MmObject 1', $series_main);
		$mm2 = $this->createMultimediaObjectAssignedToSeries('MmObject 2', $series_wall);
		$mm3 = $this->createMultimediaObjectAssignedToSeries('MmObject 3', $series_main);
		$mm4 = $this->createMultimediaObjectAssignedToSeries('MmObject 4', $series_lhazar);
	
		$this->dm->persist($mm1);
		$this->dm->persist($mm2);
		$this->dm->persist($mm3);
		$this->dm->persist($mm4);
		$this->dm->flush(); // It is needed to flush multimedia objects before pimo's

		$this->addPersonWithRoleInMultimediaObject ($person_ned, $role_lord, $mm1);
		$this->addPersonWithRoleInMultimediaObject ($person_benjen, $role_ranger, $mm2);
		$this->addPersonWithRoleInMultimediaObject ($person_ned, $role_lord, $mm3);
		$this->addPersonWithRoleInMultimediaObject ($person_benjen, $role_ranger, $mm3);
		$this->addPersonWithRoleInMultimediaObject ($person_ned, $role_hand, $mm4);
		$this->dm->flush();
		// DB setup END.

		// Testing the persistance of MultimediaObject
		$mm1_id = $mm1->getId();
		$mm2_id = $mm2->getId();
		$mm3_id = $mm3->getId();
		$mm4_id = $mm4->getId();

		// Get repository object by id
		$mm1_query = $this->dm->find('PumukitSchemaBundle:MultimediaObject', $mm1_id);
		$this->assertEquals("Stark's growing pains", $mm1_query->getSeries()->getTitle());
		$mm2_query = $this->dm->find('PumukitSchemaBundle:MultimediaObject', $mm2_id);
		$this->assertEquals("The Wall", $mm2_query->getSeries()->getTitle());
		$mm3_query = $this->dm->find('PumukitSchemaBundle:MultimediaObject', $mm3_id);
		$this->assertEquals("Stark's growing pains", $mm3_query->getSeries()->getTitle());
		$mm4_query = $this->dm->find('PumukitSchemaBundle:MultimediaObject', $mm4_id);
		$this->assertEquals("A quiet life", $mm4_query->getSeries()->getTitle());

		// Get document by field
		$mm1_doc_title = $this->repo->findByTitle($mm1->getTitle());
		$this->assertEquals(1, count($mm1_doc_title));

		// Test find by person (and role)
		$person_ned_repo = $this->dm->find('PumukitSchemaBundle:Person', $person_ned->getId());
		$person_benjen_repo = $this->dm->find('PumukitSchemaBundle:Person', $person_benjen->getId());
		$role_lord_repo = $this->dm->find('PumukitSchemaBundle:Role', $role_lord->getId());
		$role_ranger_repo = $this->dm->find('PumukitSchemaBundle:Role', $role_ranger->getId());
		$role_hand_repo = $this->dm->find('PumukitSchemaBundle:Role', $role_hand->getId());

		// person is EmbedOne in PersonInMultimediaObject		
		$pimo_repo = $this->dm->getRepository('PumukitSchemaBundle:PersonInMultimediaObject');
		$pimo_ned = $pimo_repo->findByPerson($person_ned);
		$pimo_benjen = $pimo_repo->findByPerson($person_benjen);
		$this->assertEquals(3, count($pimo_ned));
		$this->assertEquals(2, count($pimo_benjen));
		// Equivalent way to make the query
		$pimo_ned_eq = $this->dm
				->createQueryBuilder('PumukitSchemaBundle:PersonInMultimediaObject')
				->field('person')->equals($person_ned)
				->getQuery()->execute();
		$this->assertEquals(3, count($pimo_ned_eq));

		// role is ReferenceOne in PersonInMultimediaObject
		$pimo_lord = $this->dm
				->createQueryBuilder('PumukitSchemaBundle:PersonInMultimediaObject')
				->field('role')->references($role_lord_repo)
				->getQuery()->execute();
		$this->assertEquals(2, count($pimo_lord));

		$pimo_ranger = $this->dm
				->createQueryBuilder('PumukitSchemaBundle:PersonInMultimediaObject')
				->field('role')->references($role_ranger_repo)
				->getQuery()->execute();
		$this->assertEquals(2, count($pimo_ranger));

		$pimo_hand = $this->dm
				->createQueryBuilder('PumukitSchemaBundle:PersonInMultimediaObject')
				->field('role')->references($role_hand_repo)
				->getQuery()->execute();
		$this->assertEquals(1, count($pimo_hand));

		// person and role in PersonInMultimediaObject
		$pimo_ned_lord = $this->dm
				->createQueryBuilder('PumukitSchemaBundle:PersonInMultimediaObject')
				->field('person')->equals($person_ned)
				->field('role')->references($role_lord_repo)
				->getQuery()->execute();
		$this->assertEquals(2, count($pimo_ned_lord));

		$pimo_ned_hand = $this->dm
				->createQueryBuilder('PumukitSchemaBundle:PersonInMultimediaObject')
				->field('person')->equals($person_ned)
				->field('role')->references($role_hand_repo)
				->getQuery()->execute();
		$this->assertEquals(1, count($pimo_ned_hand));

		$pimo_benjen_ranger = $this->dm
				->createQueryBuilder('PumukitSchemaBundle:PersonInMultimediaObject')
				->field('person')->equals($person_benjen)
				->field('role')->references($role_ranger_repo)
				->getQuery()->execute();
		$this->assertEquals(2, count($pimo_benjen_ranger));

		$pimo_benjen_lord = $this->dm
				->createQueryBuilder('PumukitSchemaBundle:PersonInMultimediaObject')
				->field('person')->equals($person_benjen)
				->field('role')->references($role_lord_repo)
				->getQuery()->execute();
		$this->assertEquals(0, count($pimo_benjen_lord));

		// Buscamos el numero de objetos multimedia y no el numero de personas en el objeto multimedia

		//$this->assertEquals(3,count($this->repo->findByPersonAndRole($person_ned)));
		//$this->assertEquals(2,count($this->repo->findByPersonAndRole($person_ned,$role_lord)));
		//$this->assertEquals(0,count($this->repo->findByPersonAndRole($person_ned,$role_ranger)));
		//$this->assertEquals(1,count($this->repo->findByPersonAndRole($person_ned,$role_hand)));

	    		
		// Test find by series
		$series_main_repo = $this->dm
				->find('PumukitSchemaBundle:Series', $series_main->getId());
		$mmobj_series_main = $this->dm
					->createQueryBuilder('PumukitSchemaBundle:MultimediaObject')
					->field('series')->references($series_main_repo)
					->getQuery()->execute();
		$this->assertEquals(2, count($mmobj_series_main));

		$series_wall_repo = $this->dm
				->find('PumukitSchemaBundle:Series', $series_wall->getId());
		$mmobj_series_wall = $this->dm
					->createQueryBuilder('PumukitSchemaBundle:MultimediaObject')
					->field('series')->references($series_wall_repo)
					->getQuery()->execute();
		$this->assertEquals(1, count($mmobj_series_wall));
	
		$series_lhazar_repo = $this->dm
				->find('PumukitSchemaBundle:Series', $series_lhazar->getId());
		$mmobj_series_lhazar = $this->dm
					->createQueryBuilder('PumukitSchemaBundle:MultimediaObject')
					->field('series')->references($series_lhazar_repo)
					->getQuery()->execute();
		$this->assertEquals(1, count($mmobj_series_lhazar));	
	
	}


	public function testFindBySeries()
	  {
	  $this->assertEquals(0, count($this->repo->findAll()));
	  //$this->assertEquals(4,count($this->repo->findBySeries($series_main)));
	}

	private function createPerson($name)
	{
		$email = $name . '@mail.es';
		$web = 'http://www.url.com';
		$phone = '+34986123456';
		$honorific = 'honorific';
		$firm = 'firm';
		$post = 'post';
		$bio = 'Biografía extensa de la persona';

		$person = new Person();
		$person->setName($name);
		$person->setEmail($email);
		$person->setWeb($web);
		$person->setPhone($phone);
		$person->setHonorific($honorific);
		$person->setFirm($firm);
		$person->setPost($post);
		$person->setBio($bio);

		// FIXME esto no persiste.
		// Este dm (DocumentManager) se refiere
		// a doctrine_mongodb en la
		// base de datos pumukit_test
		$this->dm->persist($person);
		$this->dm->flush();

		return $person;
	}


	private function createRole($name)
	{
		$cod = $name; // string (20)
		$rank = strlen($name); // Quick and dirty way to keep it unique
		$xml = '<xml content and definition of this/>';
		$display = true;
		$text = 'Black then white are all i see in my infancy,
			red and yellow then came to be, reaching out to me,
			lets me see.
			As below, so above and beyond, I imagine
			drawn beyond the lines of reason.
			Push the envelope. Watch it bend.';
		$pimo = new PersonInMultimediaObject();

		$rol = new Role();
		$rol->setCod($cod);
		$rol->setRank($rank);
		$rol->setXml($xml);
		$rol->setDisplay($display); // true by default
		$rol->setName($name);
		$rol->setText($text);
		$rol->addPeopleInMultimediaObject($pimo);

		$this->dm->persist($rol);
		$this->dm->flush();

		return $rol;
	}

	private function createMultimediaObjectAssignedToSeries($title, Series $series)
	{
		$rank = 1;
		$status = MultimediaObject::STATUS_NORMAL;
		$record_date = new \DateTime();
		$public_date = new \DateTime();
		$subtitle = 'Subtitle';
		$description = "Description";
		$duration = 123;

		//$tag1 = new Tag('tag1');
		//$tag2 = new Tag('tag2');
		//$mm_tags = array($tag1, $tag2);

		//$track1 = new Track();

		//$pic1 = new Pic();

		//$material1 = new Material();

		$mm = new MultimediaObject();

		//$mm->addTag($tag1);
		//$mm->addTrack($track1);
		//$mm->addPic($pic1);
		//$mm->addMaterial($material1);

		$mm->setStatus($status);
		$mm->setRecordDate($record_date);
		$mm->setPublicDate($public_date);
		$mm->setTitle($title);
		$mm->setSubtitle($subtitle);
		$mm->setDescription($description);
		$mm->setDuration($duration);

		$series->addMultimediaObject($mm);

		//$this->dm->persist($tag1);
		//$this->dm->persist($track1);
		//$this->dm->persist($pic1);
		//$this->dm->persist($material1);
		$this->dm->persist($mm);
		$this->dm->flush();

		return $mm;
	}


	private function createSeries($title)
	{
		$subtitle = 'subtitle';
		$description = 'description';
		$test_date = new \DateTime("now");

		$serie = new Series();

		$serie->setTitle($title);
		$serie->setSubtitle($subtitle);
		$serie->setDescription($description);
		$serie->setPublicDate($test_date);

		$this->dm->persist($serie);
		$this->dm->flush();

		return $serie;
	}

	private function createSeriesType($name)
	{
		$description = 'description';
	   	$series_type = new SeriesType();

	   	$series_type->setName($name);
	   	$series_type->setDescription($description);

	   	$this->dm->persist($series_type);
		$this->dm->flush();

	   	return $series_type;
	}


	// This function was used to assure that pimo objects would persist.
	public function addPersonWithRoleInMultimediaObject(
			Person $person, Role $role, MultimediaObject $mm)
	{
		if (!$mm->containsPersonWithRole($person, $role)) {
			$pimo = new PersonInMultimediaObject();
			$pimo->setPerson($person);
			$pimo->setRole($role);
			$pimo->setMultimediaObject($mm);
			$pimo->setRank(count($mm->getPeopleInMultimediaObject()));
			$mm->addPersonInMultimediaObject($pimo);
			$this->dm->persist($pimo);
			$this->dm->flush();
		}
	}
}
