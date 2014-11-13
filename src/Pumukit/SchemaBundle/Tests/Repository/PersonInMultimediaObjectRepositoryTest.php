<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\PersonInMultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class PersonInMultimediaObjectRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;

    public function setUp()
    {
        //INIT TEST SUITE
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();
        $this->dm = $kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository('PumukitSchemaBundle:PersonInMultimediaObject');

        //DELETE DATABASE
        $this->dm->getDocumentCollection('PumukitSchemaBundle:PersonInMultimediaObject')
            ->remove(array());
    }

    public function testRepositoryEmpty()
    {
        $this->assertEquals(0, count($this->repo->findAll()));
    }

    public function testRepository()
    {
        $person = $this->createPerson('Juan Alvarez');
        $role = $this->createRole('Actor');
        $mmobj = $this->createMultimediaObject('Multimedia Object 1');
        $rank = 2;

        $pimmobj = new PersonInMultimediaObject();

        $pimmobj->setPerson($person);
        $pimmobj->setRole($role);
        $pimmobj->setMultimediaObject($mmobj);
        $pimmobj->setRank($rank);

        $this->dm->persist($pimmobj);
        $this->dm->flush();

        $this->assertEquals(1, count($this->repo->findAll()));
    }

    private function createPerson($name)
    {
        $email = str_replace(' ', '', $name).'@email.com';
        $web = str_replace(' ', '', $name).'.com';
        $phone = '987655465';
        $honorific = 'honorific '.$name;
        $firm = 'firm '.$name;
        $post = 'post commented by '.$name;
        $bio = 'biography of '.$name;

        $person = new Person();

        $person->setName($name);
        $person->setEmail($email);
        $person->setWeb($web);
        $person->setPhone($phone);
        $person->setHonorific($honorific);
        $person->setFirm($firm);
        $person->setPost($post);
        $person->setBio($bio);

        $this->dm->persist($person);
        $this->dm->flush();

        return $person;
    }

    private function createRole($name)
    {
        $cod = 24421;
        $rank = 3;
        $xml = '<xml>definiciones</xml>';
        $display = true;
        $text = 'texto del rol explicando su rol';

        $role = new Role();

        $role->setName($name);
        $role->setCod($cod);
        $role->setRank($rank);
        $role->setXml($xml);
        $role->setDisplay($display);
        $role->setText($text);

        $this->dm->persist($role);
        $this->dm->flush();

        return $role;
    }

    private function createMultimediaObject($title)
    {
        $rank = 1;
        $status = MultimediaObject::STATUS_NORMAL;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $subtitle = 'subtitle of this Multimedia Object';
        $description = 'description of this multimedia object';

        $mmobj = new MultimediaObject();

        $mmobj->setTitle($title);
        $mmobj->setRank($rank);
        $mmobj->setStatus($status);
        $mmobj->setRecordDate($record_date);
        $mmobj->setPublicDate($public_date);
        $mmobj->setSubtitle($subtitle);
        $mmobj->setDescription($description);

        $this->dm->persist($mmobj);
        $this->dm->flush();

        return $mmobj;
    }

    private function createPersonInMultimediaObject()
    {
    }
}
