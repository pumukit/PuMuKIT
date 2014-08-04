<?php

namespace Pumukit\SchemaBundle\Tests\Entity;

use Pumukit\SchemaBundle\Entity\Role;

class RoleTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaults()
    {
        $role = new Role();

        $this->assertEquals(0,$role->getCod());
        $this->assertTrue($role->getDisplay());
    }

    public function testGetterAndSetter()
    {
        $role = new Role();

        $cod = 12345; //String - max length = 5
        $rank = 123;
        $xml = 'string <xml>';
        $display = true;
        $name = 'Alonso de Lanzós';
        $text = 'The Irmandiño Wars were two revolts that took place in 15th-century Kingdom of Galicia against attempts by the regional nobility to maintain their rights over the peasantry and the bourgeoisie. The revolts were also part of the larger phenomenon of popular revolts in late medieval Europe caused by the general economic and demographic crises in Europe during the fourteenth and fifteenth centuries.[1] Similar rebellions broke out in the Iberian Kingdoms, including the War of the Remences in Catalonia and the foráneo revolts in the Balearic Islands.[2]';

        $role->setCod($cod);
        $role->setRank($rank);
        $role->setXml($xml);
        $role->setDisplay($display);
        $role->setName($name);
        $role->setText($text);

        $this->assertEquals($cod, $role->getCod($cod));
        $this->assertEquals($rank, $role->getRank($rank));
        $this->assertEquals($xml, $role->getXml($xml));
        $this->assertEquals($display, $role->getDisplay($display));
        $this->assertEquals($name, $role->getName($name));
        $this->assertEquals($text, $role->getText($text));
    }

}
