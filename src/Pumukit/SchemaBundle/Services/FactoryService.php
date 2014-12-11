<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Broadcast;

class FactoryService
{
    const DEFAULT_SERIES_TITLE = 'New';
    const DEFAULT_MULTIMEDIAOBJECT_TITLE = 'New';

    private $dm;
    private $translator;
    private $locales;

    public function __construct(DocumentManager $documentManager, TranslatorInterface $translator, array $locales = array())
    {
        $this->dm = $documentManager;
        $this->translator = $translator;
        $this->locales = $locales;
    }

    /**
     * Get locales
     */
    public function getLocales()
    {
      return $this->locales;
    }

  /**
   * Create a new series with default values
   *
   * @return Series
   */
  public function createSeries()
  {
      $series = new Series();

      $series->setPublicDate(new \DateTime("now"));
      $series->setCopyright('UdN-TV');
      foreach ($this->locales as $locale) {
          $title = $this->translator->trans(self::DEFAULT_SERIES_TITLE, array(), null, $locale);
          $series->setTitle($title, $locale);
      }

      $this->dm->persist($series);
      $this->dm->flush();

      $mm = $this->createMultimediaObjectTemplate($series);

      return $series;
  }

  /**
   * Create a new Multimedia Object Template
   *
   * @return MultimediaObject
   */
  public function createMultimediaObjectTemplate($series)
  {
      $mm = new MultimediaObject();
      $mm->setStatus(MultimediaObject::STATUS_PROTOTYPE);
      $mm->setBroadcast($this->getDefaultBroadcast());
      $mm->setPublicDate(new \DateTime("now"));
      $mm->setRecordDate($mm->getPublicDate());
      foreach ($this->locales as $locale) {
          $title = $this->translator->trans(self::DEFAULT_MULTIMEDIAOBJECT_TITLE, array(), null, $locale);
          $mm->setTitle($title, $locale);
      }

      $mm->setSeries($series);

      $this->dm->persist($mm);
      $this->dm->flush();

      return $mm;
  }

  /**
   * Create a new Multimedia Object from Template
   *
   * @return MultimediaObject
   */
  public function createMultimediaObject($series)
  {
      $prototype = $this->dm
    ->getRepository('PumukitSchemaBundle:MultimediaObject')
    ->findPrototype($series);

      if (null != $prototype) {
          $mm = $prototype->cloneResource();
      } else {
          $mm = new MultimediaObject();
          $mm->setBroadcast($this->getDefaultBroadcast());
          $mm->setPublicDate(new \DateTime("now"));
          $mm->setRecordDate($mm->getPublicDate());
          foreach ($this->locales as $locale) {
              $title = $this->translator->trans(self::DEFAULT_MULTIMEDIAOBJECT_TITLE, array(), null, $locale);
              $mm->setTitle($title, $locale);
          }
      }

      $mm->setStatus(MultimediaObject::STATUS_NEW);

      $series->addMultimediaObject($mm);

      $this->dm->persist($mm);
      $this->dm->persist($series);
      $this->dm->flush();

      return $mm;
  }

  /**
   * Gets default broadcast or public one
   *
   * @return Broadcast
   */
  public function getDefaultBroadcast()
  {
      $broadcast = $this->dm
    ->getRepository('PumukitSchemaBundle:Broadcast')
    ->findDefaultSel();

      if (null == $broadcast) {
          $broadcast = $this->dm
      ->getRepository('PumukitSchemaBundle:Broadcast')
      ->findPublicBroadcast();
      } else {
          // TODO throw exception
      }

      return $broadcast;
  }
}
