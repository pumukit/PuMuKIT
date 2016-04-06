<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Pic;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Finder\Finder;

class SeriesService
{
    private $dm;
    private $repo;
    private $seriesDispatcher;

    public function __construct(DocumentManager $documentManager, SeriesEventDispatcherService $seriesDispatcher)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:Series');
        $this->seriesDispatcher = $seriesDispatcher;
    }
    
   /**
     * Resets the magic url for a given series. Returns the secret id.
     *
     * @param Series
     * @return String
     */
    public function resetMagicUrl($series){
        $series->resetSecret();
        $this->dm->persist($series);
        $this->dm->flush();
        $this->seriesDispatcher->dispatchUpdate($series);

        return $series->getSecret();
    }
 
}