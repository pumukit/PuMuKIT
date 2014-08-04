<?php
namespace Pumukit\SchemaBundle\Tests\Entity;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Pumukit\SchemaBundle\Entity\Series;
use Pumukit\SchemaBundle\Entity\SeriesType;

abstract class CommonCreateFunctions extends WebTestCase
{
     public static function createSeries($em, $title)
    {
        $subtitle    = 'subtitle';
        $description = 'description';
        $test_date   = new \DateTime("now");
        $serie       = new Series();

        $serie->setTitle($title);
        $serie->setSubtitle($subtitle);
        $serie->setDescription($description);
        $serie->setPublicDate($test_date);

        $em->persist($serie);

        return $serie;
    }

    public static function createSeriesType($em, $name)
    {
        $description = 'description';
        $series_type = new SeriesType();

        $series_type->setName($name);
        $series_type->setDescription($description);

        $em->persist($series_type);

        return $series_type;
    }

}
