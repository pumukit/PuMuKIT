<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\PersonInMultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class PersonInMultimediaObjectTest extends \PHPUnit_Framework_TestCase
{
    public function testGetterAndSetter()
    {
        $person = $this->createPerson('Juan Alvarez');
        $role = $this->createRole('Actor');
        $mmobj = $this->createMultimediaObject('Multimedia Object 1');
        $rank = 11;

        $pimmobj = new PersonInMultimediaObject();

        //$person->addPeopleInMultimediaObject($pimmobj);
        //$role->addPeopleInMultimediaObject($pimmobj);

        $pimmobj->setPerson($person);
        $pimmobj->setRole($role);
        $pimmobj->setMultimediaObject($mmobj);
        $pimmobj->setRank($rank);

        $this->assertEquals($person, $pimmobj->getPerson());
        $this->assertEquals($role, $pimmobj->getRole());
        $this->assertEquals($mmobj, $pimmobj->getMultimediaObject());
        $this->assertEquals($rank, $pimmobj->getRank());
        //$this->assertEquals($pimmobj, $person->getPeopleInMultimediaObject());
        //$this->assertEquals($role->getPeopleInMultimediaObject(), $pimmobj);
    }

    private function createPerson($name)
    {
        $email = str_replace(' ', '', $name).'@email.com';
        $web = str_replace(' ', '', $name).'.com';
        $phone = '654765921';
        $honorific = 'honorific';
        $firm = 'firm';
        $post = 'post comments';
        $bio = 'biography of this person';

        $person = new Person();

        $person->setName($name);
        $person->setEmail($email);
        $person->setWeb($web);
        $person->setPhone($phone);
        $person->setHonorific($honorific);
        $person->setFirm($firm);
        $person->setPost($post);
        $person->setBio($bio);

        return $person;
    }

    private function createRole($name)
    {
        $cod = 32135;
        $rank = 1;
        $xml = '<xml>cadenas</xml>';
        $display = true;
        $text = 'Texto del rol en sí';

        $role = new Role();

        $role->setName($name);
        $role->setCod($cod);
        $role->setRank($rank);
        $role->setXml($xml);
        $role->setDisplay($display);
        $role->setText($text);

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

        return $mmobj;
    }
}
