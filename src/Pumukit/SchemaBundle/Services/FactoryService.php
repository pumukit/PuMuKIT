<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;

use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class FactoryService
{
  private $dm;


  public function __construct(DocumentManager $documentManager)
  {
    $this->dm = $documentManager;
  }

  /**
   * Create a new series with default values
   */
  public function createSeries()
  {
      $series = new Series();

      $series->setPublicDate(new \DateTime("now"));
      $series->setTitle('New');
      $series->setCopyright('UdN-TV');

      $this->dm->persist($series);
      $this->dm->flush();
  }

  /**
   * Create a new series with default values
   */
  public function createMultimediaObject()
  {
      $mm = new MultimediaObject();

      $mm->setPublicDate(new \DateTime("now"));
      $mm->setRecordDate($mm->getPublicDate());
      $mm->setTitle('New');

      $this->dm->persist($mm);
      $this->dm->flush();
  }

}
