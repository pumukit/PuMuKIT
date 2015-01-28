<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\SeriesType;

class SeriesTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testGetterAndSetter()
    {
        $name = "Jules' sermon";
        $description = "Ezekiel 25:17. The path of the righteous man is beset on all sides by the iniquities of the selfish and the tyranny of evil men.";

        $series_type = new SeriesType();

        $series_type->setName($name);
        $series_type->setDescription($description);

        $this->assertEquals($name, $series_type->getName());
        $this->assertEquals($description, $series_type->getDescription());
    }
}
