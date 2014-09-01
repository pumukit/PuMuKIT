<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Series;

class SeriesTest extends \PHPUnit_Framework_TestCase
{

  public function testGetterAndSetter()
  {
    $title = 'title';
    $subtitle = 'subtitle';
    $description = 'description';
    $test_date = new \DateTime("now");

    $serie = new Series();

    $serie->setTitle($title);
    $serie->setSubtitle($subtitle);
    $serie->setDescription($description);
    $serie->setPublicDate($test_date);

    $this->assertEquals($title, $serie->getTitle());
    $this->assertEquals($subtitle, $serie->getSubtitle());
    $this->assertEquals($description, $serie->getDescription());
    $this->assertEquals($test_date, $serie->getPublicDate());
  }
}
