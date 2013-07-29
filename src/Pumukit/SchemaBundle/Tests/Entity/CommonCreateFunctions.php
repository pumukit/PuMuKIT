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