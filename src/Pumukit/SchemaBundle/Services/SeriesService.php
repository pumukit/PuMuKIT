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

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:Series');
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
        return $series->getSecret();
    }
 
}