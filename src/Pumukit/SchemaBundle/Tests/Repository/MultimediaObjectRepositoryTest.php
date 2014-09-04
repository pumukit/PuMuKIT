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

class MultimediaObjectRepositoryTest extends WebTestCase
{
	private $dm;
	private $repo;

	public function setUp()
	{
		$options = array(
				'environment' => 'test'
				);
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
		$this->dm->flush();

		//$this->dm->getDocumentCollection('PumukitSchemaBundle:SeriesType')->remove(array());
		//$this->dm->flush();
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
		//$series_type = $this->createSeriesType("Medieval Fantasy Sitcom");
		//$series_type = CommonCreateFunctions::createSeriesType($this->em, "prueba");

		$series_main = $this->createSeries("Stark's growing pains");
		$series_wall = $this->createSeries("The Wall");
		$series_lhazar = $this->createSeries("A quiet life");

		$person_ned = $this->createPerson('Ned');
		$person_benjen = $this->createPerson('Benjen');

		$role_lord = $this->createRole("Lord");
		$role_ranger = $this->createRole("First Ranger");
		$role_hand = $this->createRole("Hand of the King");

		//$series_type->addSeries($series_main);
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

		// Test find by person (and role)
		//$qb = $this->dm->createQueryBuilder('PumukitSchemaBundle:MultimediaObject')->field('people_in_multimedia_object')->equals($person_ned);
		$qb = $this->dm->createQueryBuilder('PumukitSchemaBundle:MultimediaObject')->field('rank')->equals(1);
		$query = $qb->getQuery();
		$results = $query->execute();
		$this->assertEquals(3,count($results));

		//$this->assertEquals(3,count($this->repo->findByPersonAndRole($person_ned)));
		//$this->assertEquals(2,count($this->repo->findByPersonAndRole($person_ned,$role_lord)));
		//$this->assertEquals(0,count($this->repo->findByPersonAndRole($person_ned,$role_ranger)));
		//$this->assertEquals(1,count($this->repo->findByPersonAndRole($person_ned,$role_hand)));

		/*
		// Test find by series
		$this->assertEquals(3,count($this->repo->findBySeries($series_main)));
		$this->assertEquals(1,count($this->repo->findBySeries($series_wall)));
		$this->assertEquals(0,count($this->repo->findBySeries($series_lhazar)));
		$this->assertEquals(2,count($this->repo->findBySeries($series_main, 2)));
		// exit("\n Intentando salir del test phpunit con un exit\n");
		// // passthru('read ');*/

		/*	
			$titulo = $this->dm->createQueryBuilder('PumukitSchemaBundle:MultimediaObject')
			->field('title')->equals('titulo cualquiera')
			->getQuery()
			->getSingleResult();

			$qb = $this->dm->createQueryBuilder();*/

		/*
		   ->field('title')->equals('titulo cualquiera')
		   ->getQuery()
		   ->getSingleResult();*/
		//$this->assertEquals('titulo cualquiera', $titulo);
	}


	/*public function testFindBySeries()
	  {
	  $this->assertEquals(0, count($this->repo->findAll()));
	//$this->assertEquals(4,count($this->repo->findBySeries($series_main)));

	}*/

	private function createPerson($name)
	{
		$email = $name . "@pr.es";
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

		// FIXME esto no persiste. ¿Sera porque no tiene repositorio?
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
		$text = 'Black then white are all i see in my infancy.
			red and yellow then came to be, reaching out to me.
			lets me see.
			as below, so above and beyond, I imagine
			drawn beyond the lines of reason.
			Push the envelope. Watch it bend.';
		//$pimo = new PersonInMultimediaObject();

		$rol = new Role();
		$rol->setCod($cod);
		$rol->setRank($rank);
		$rol->setXml($xml);
		$rol->setDisplay($display); // true by default
		$rol->setName($name);
		$rol->setText($text);

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

	/*
	   private function createSeriesType($name)
	   {
	   $description = 'description';
	   $series_type = new SeriesType();

	   $series_type->setName($name);
	   $series_type->setDescription($description);

	   $this->em->persist($series_type);

	   return $series_type;
	   }*/


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
