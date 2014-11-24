<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Doctrine\ODM\MongoDB\DocumentManager;

use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class FactoryService
{
  const DEFAULT_SERIES_TITLE = 'New';
  const DEFAULT_MULTIMEDIAOBJECT_TITLE = 'New';

  private $dm;
  private $translator;
  private $locales;

  public function __construct(DocumentManager $documentManager, Translator $translator, array $locales = array())
  {
      $this->dm = $documentManager;
      $this->translator = $translator;
      $this->locales = $locales;
  }

  /**
   * Create a new series with default values
   */
  public function createSeries()
  {
      $series = new Series();

      $series->setPublicDate(new \DateTime("now"));
      $series->setCopyright('UdN-TV');
      foreach ($this->locales as $locale) {
	  $series->setTitle($this->translator->trans(self::DEFAULT_SERIES_TITLE), $locale);
      }

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
      foreach ($this->locales as $locale) {
	  $mm->setTitle($this->translator->trans(self::DEFAULT_MULTIMEDIAOBJECT_TITLE), $locale);
      }

      $this->dm->persist($mm);
      $this->dm->flush();
  }

}
