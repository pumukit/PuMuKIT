<?php

namespace Pumukit\SchemaBundle\Tests\Entity;

use Pumukit\SchemaBundle\Entity\Series;
use Pumukit\SchemaBundle\Entity\SeriesType;

class SeriesTypeTest extends \PHPUnit_Framework_TestCase
{

    public function testGetterAndSetter()
    {
        $name        = "Jules' sermon";
        $description = "Ezekiel 25:17. The path of the righteous man is beset on all sides by the iniquities of the selfish and the tyranny of evil men.";

        $series_type = new SeriesType();

        $series_type->setName($name);
        $series_type->setDescription($description);

        $this->assertEquals($name, $series_type->getName());
        $this->assertEquals($description, $series_type->getDescription());
    }

    public function testSeriesInSeriesType()
    {
        $series_type = new SeriesType();
        $series1 = new Series();
        $series2 = new Series();
        $series3 = new Series();

        $this->assertEquals(0, count($series_type->getSeries()));

        $series_type->addSeries($series1);
        $series_type->addSeries($series2);
        $series_type->addSeries($series3);
        $this->assertEquals(3, count($series_type->getSeries()));

        $series_type->removeSeries($series2);
        $this->assertEquals(2, count($series_type->getSeries()));

        $this->assertTrue($series_type->containsSeries($series1));
        $this->assertFalse($series_type->containsSeries($series2));
    }
}
