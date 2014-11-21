<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class FactoryService
{
  /**
   * Create a new series with default values
   */
  public function createSeries($controller)
  {
      $dm = $controller->get('doctrine_mongodb')->getManager();

      $series = $controller->createNew();

      $series->setPublicDate(new \DateTime("now"));
      $series->setTitle('New');
      $series->setCopyright('UdN-TV');

      $dm->persist($series);
      $dm->flush();
  }

  /**
   * Create a new series with default values
   */
  public function createMultimediaObject($controller)
  {
      $dm = $controller->get('doctrine_mongodb')->getManager();

      $mm = $controller->createNew();

      $mm->setPublicDate(new \DateTime("now"));
      $mm->setRecordDate($mm->getPublicDate());
      $mm->setTitle('New');

      $dm->persist($mm);
      $dm->flush();
  }

}
