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

        $mm = $this->createMultimediaObjectTemplate($series);

        $this->dm->persist($mm);
        $this->dm->persist($series);
        $this->dm->flush();

        //Workaround to fix reference method initialization.
        $this->dm->clear(get_class($series));
        return $this->dm->find('PumukitSchemaBundle:Series', $series->getId());
    }

    /**
     * Create a new Multimedia Object Template
     *
     * @return MultimediaObject
     */
    private function createMultimediaObjectTemplate($series)
    {
        $mm = new MultimediaObject();
        $mm->setStatus(MultimediaObject::STATUS_PROTOTYPE);
        try {
            $broadcast = $this->getDefaultBroadcast();
            $mm->setBroadcast($broadcast);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        $mm->setPublicDate(new \DateTime("now"));
        $mm->setRecordDate($mm->getPublicDate());
        foreach ($this->locales as $locale) {
            $title = $this->translator->trans(self::DEFAULT_MULTIMEDIAOBJECT_TITLE, array(), null, $locale);
            $mm->setTitle($title, $locale);
        }

        $mm->setSeries($series);

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

        if (null !== $prototype) {
            $mm = $this->createMultimediaObjectFromPrototype($prototype);
        } else {
            $mm = new MultimediaObject();
            foreach ($this->locales as $locale) {
                $title = $this->translator->trans(self::DEFAULT_MULTIMEDIAOBJECT_TITLE, array(), null, $locale);
                $mm->setTitle($title, $locale);
            }
        }
        try {
            $broadcast = $this->getDefaultBroadcast();
            $mm->setBroadcast($broadcast);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        $mm->setPublicDate(new \DateTime("now"));
        $mm->setRecordDate($mm->getPublicDate());
        $mm->setStatus(MultimediaObject::STATUS_NEW);

        $mm->setSeries($series);

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
        $repoBroadcast = $this->dm->getRepository('PumukitSchemaBundle:Broadcast');

        $broadcast = $repoBroadcast->findDefaultSel();

        if (null == $broadcast) {
            $broadcast = $repoBroadcast->findPublicBroadcast();
        }

        if (null == $broadcast) {
            throw new \Exception('There is no default selected broadcast neither public broadcast.');
        }

        return $broadcast;
    }

    /**
     * Create multimedia object from prototype
     *
     * @param MultimediaObject $prototype
     * @return MultimediaObject
     */
    private function createMultimediaObjectFromPrototype(MultimediaObject $prototype)
    {
        $new = new MultimediaObject();

        //$new->setRank($prototype->getRank()); //SortablePosition
        $new->setI18nTitle($prototype->getI18nTitle());
        $new->setI18nSubtitle($prototype->getI18nSubtitle());
        $new->setI18nDescription($prototype->getI18nDescription());
        $new->setI18nLine2($prototype->getI18nLine2());
        $new->setI18nCopyright($prototype->getI18nCopyright());
        $new->setI18nKeyword($prototype->getI18nKeyword());
        $new->setDuration($prototype->getDuration());
        $new->setNumview($prototype->getNumview());

        foreach ($prototype->getTags() as $tag) {
            $new->addTag($tag);
        }

        foreach ($prototype->getRoles() as $embeddedRole) {
            foreach ($embeddedRole->getPeople() as $embeddedPerson) {
                $new->addPersonWithRole($embeddedPerson, $embeddedRole);
            }
        }

        return $new;
    }
}
