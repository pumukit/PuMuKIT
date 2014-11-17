<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Series;

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
   * Create new Multimedia Object with default values
   */
  public function createMultimediaObject($controller)
  {
  }
}
