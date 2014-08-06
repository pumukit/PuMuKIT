<?php
namespace Pumukit\SchemaBundle\Tests\Entity;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Pumukit\SchemaBundle\Entity\MultimediaObject;
use Pumukit\SchemaBundle\Entity\Series;
use Pumukit\SchemaBundle\Entity\SeriesType;
use Pumukit\SchemaBundle\Entity\Track;
use Pumukit\SchemaBundle\Entity\Pic;
use Pumukit\SchemaBundle\Entity\Material;
use Pumukit\SchemaBundle\Entity\Tag;
use Pumukit\SchemaBundle\Entity\Person;
use Pumukit\SchemaBundle\Entity\Role;
use Pumukit\SchemaBundle\Entity\PersonInMultimediaObject;

class MultimediaObjectRepositoryTest extends WebTestCase
{

    private $em;
    private $repo;

    public function setUp()
    {
        //INIT TEST SUITE
        $kernel = static::createKernel();
        $kernel->boot();
        $this->em = $kernel->getContainer()
            ->get('doctrine.orm.entity_manager');
        $this->repo = $this->em
            ->getRepository('PumukitSchemaBundle:MultimediaObject');

        //DELETE DATABASE - pimo has to be deleted before mm
        $this->em->createQuery("DELETE PumukitSchemaBundle:PersonInMultimediaObject pimo")->getResult();
        $this->em->createQuery("DELETE PumukitSchemaBundle:MultimediaObject mm")->getResult();
        $this->em->createQuery("DELETE PumukitSchemaBundle:Role r")->getResult();
        $this->em->createQuery("DELETE PumukitSchemaBundle:Person p")->getResult();
        $this->em->createQuery("DELETE PumukitSchemaBundle:Series s")->getResult();
        $this->em->createQuery("DELETE PumukitSchemaBundle:SeriesType st")->getResult();

    }

    public function testRepositoryEmpty()
    {
        $this->assertEquals(0, count($this->repo->findAll()));
    }

    // Review MultimediaObject->addPersonWithRole
    // Review CommonCreateFunctions - em->persist throws error
    public function testCreateMultimediaObjectAndFindByCriteria()
    {

        $series_type   = $this->createSeriesType("Medieval Fantasy Sitcom");
        //$series_type = CommonCreateFunctions::createSeriesType($this->em, "prueba");
        $series_main   = $this->createSeries("Stark's growing pains");
        $series_wall   = $this->createSeries("The Wall");
        $series_lhazar = $this->createSeries("A quiet life");
        $person_ned    = $this->createPerson('Ned');
        $person_benjen = $this->createPerson('Benjen');
        $role_lord     = $this->createRole("Lord");
        $role_ranger   = $this->createRole("First Ranger");
        $role_hand     = $this->createRole("Hand of the King");

        $series_type->addSeries($series_main);
        $mm1=$this->createMultimediaObjectAssignedToSeries ('MmObject 1', $series_main);
        $mm2=$this->createMultimediaObjectAssignedToSeries ('MmObject 2', $series_wall);
        $mm3=$this->createMultimediaObjectAssignedToSeries ('MmObject 3', $series_main);
        $mm4=$this->createMultimediaObjectAssignedToSeries ('MmObject 4', $series_main);

        $this->em->persist($mm1);
        $this->em->persist($mm2);
        $this->em->persist($mm3);
        $this->em->persist($mm4);
        $this->em->flush(); // It is needed to flush multimedia objects before pimo's

        $this->addPersonWithRoleInMultimediaObject ($person_ned, $role_lord, $mm1);
        $this->addPersonWithRoleInMultimediaObject ($person_benjen, $role_ranger, $mm2);
        $this->addPersonWithRoleInMultimediaObject ($person_ned, $role_lord, $mm3);
        $this->addPersonWithRoleInMultimediaObject ($person_benjen, $role_ranger, $mm3);
        $this->addPersonWithRoleInMultimediaObject ($person_ned, $role_hand, $mm4);
        $this->em->flush();
        // DB setup END.

        // Test find by person (and role)
        $this->assertEquals(3,count($this->repo->findByPersonAndRole($person_ned)));
        $this->assertEquals(2,count($this->repo->findByPersonAndRole($person_ned,$role_lord)));
        $this->assertEquals(0,count($this->repo->findByPersonAndRole($person_ned,$role_ranger)));
        $this->assertEquals(1,count($this->repo->findByPersonAndRole($person_ned,$role_hand)));

        // Test find by series
        $this->assertEquals(3,count($this->repo->findBySeries($series_main)));
        $this->assertEquals(1,count($this->repo->findBySeries($series_wall)));
        $this->assertEquals(0,count($this->repo->findBySeries($series_lhazar)));
        $this->assertEquals(2,count($this->repo->findBySeries($series_main, 2)));
        // exit("\n Intentando salir del test phpunit con un exit\n");
        // // passthru('read ');
    }

    public function testFindBySeries()
    {
        $this->assertEquals(0, count($this->repo->findAll()));
        //$this->assertEquals(4,count($this->repo->findBySeries($series_main)));

    }

    private function createPerson($name)
    {
        // login attribute is unique
        $password  = '123<>U45;';
        $email     = $name . "@pr.es";
        $web       = 'http://www.url.com';
        $phone     = '+34986123456';
        $honorific = 'honorific';
        $firm      = 'firm';
        $post      = 'post';

        $user = new Person();
        $user->setUsername($name);
        $user->setPassword($password);
        $user->setName($name);
        $user->setEmail($email);

        // FIXME esto no persiste
        $this->em->persist($user);

        return $user;
    }

    private function createRole($name)
    {
        $cod     = $name; // string (20)
        $rank    = strlen($name); // Quick and dirty way to keep it unique
        $xml     = '<xml la lala la lala la lala laaaa/>';
        $display = true;
        $text    = 'Tú tenías mucha razón, ¡no te hice caaaasooo! Hoy he de
         reconocer, ¡delante de un vaaasooo! Hoy me pesa la cabezaaaa,
         ¡qué pesaaaaaar! ¡Te juro que nesesiiiiiitoooooo
         reeeeee-greeeeee-saaaaaaar...!';
        $pimo    = new PersonInMultimediaObject();

        $rol = new Role();
        $rol->setCod($cod);
        $rol->setRank($rank);
        $rol->setXml($xml);
        // $rol->setDisplay($display); // true by default
        $rol->setName($name);
        $rol->setText($text);

        $this->em->persist($rol);

        return $rol;
    }

    private function createMultimediaObjectAssignedToSeries($title, Series $series)
    {
        $status      = MultimediaObject::STATUS_NORMAL;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $subtitle    = 'Subtitle';
        $description = "Description";
        $duration    = 123;

        // $tag1      = new Tag();
        // $track1    = new Track();
        // $pic1      = new Pic();
        // $material1 = new Material();

        $mm = new MultimediaObject();
        $series->addMultimediaObject($mm);

        // $mm->addTag($tag1);
        // $mm->addTrack($track1);
        // $mm->addPic($pic1);
        // $mm->addMaterial($material1);

        $mm->setStatus($status);
        $mm->setRecordDate($record_date);
        $mm->setPublicDate($public_date);
        $mm->setTitle($title);
        $mm->setSubtitle($subtitle);
        $mm->setDescription($description);
        $mm->setDuration($duration);
        // $this->em->persist($track1);
        // $this->em->persist($pic1);
        // $this->em->persist($material1);
        $this->em->persist($mm);

        return $mm;
    }


    private function createSeries($title)
    {
        $subtitle    = 'subtitle';
        $description = 'description';
        $test_date   = new \DateTime("now");
        $serie       = new Series();

        $serie->setTitle($title);
        $serie->setSubtitle($subtitle);
        $serie->setDescription($description);
        $serie->setPublicDate($test_date);

        $this->em->persist($serie);

        return $serie;
    }

    private function createSeriesType($name)
    {
        $description = 'description';
        $series_type = new SeriesType();

        $series_type->setName($name);
        $series_type->setDescription($description);

        $this->em->persist($series_type);

        return $series_type;
    }

    // This function was used to assure that pimo objects would persist.
    public function addPersonWithRoleInMultimediaObject(
                    Person $person, Role $role, MultimediaObject $mm)
    {
        if (!$mm->containsPersonWithRole($person, $role)) {
            $pimo = new PersonInMultimediaObject();
            $pimo->setPerson( $person );
            $pimo->setRole( $role );
            $pimo->setMultimediaObject( $mm );
            $pimo->setRank(count($mm->getPeopleInMultimediaObject()));
            $mm->addPersonInMultimediaObject($pimo);
            $this->em->persist($pimo);
        }
    }
}
