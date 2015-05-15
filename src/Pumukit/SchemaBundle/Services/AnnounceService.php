<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;

class AnnounceService
{
      private $seriesRepo;
      private $mmRepo;

      public function __construct(DocumentManager $documentManager)
      {
          $dm = $documentManager;
          $this->seriesRepo = $dm->getRepository('PumukitSchemaBundle:Series');
          $this->mmRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
      }


      public function getLast($limit = 3)
      {
          $lastMms = $this->mmRepo->findStandardBy(array('tags.cod' => 'PUDENEW'), array('public_date' => -1), 3, 0);
          $lastSeries = $this->seriesRepo->findBy(array('announce' => true), array('public_date' => -1), 3, 0);

          $return = array();
          $i = 0;
          $iMms = 0;
          $iSeries = 0;
          // TODO: Review workaround
          if ($lastMms) {
              while($i < $limit){
                  $auxMms = $lastMms[$iMms];
                  $auxSeries = $lastSeries[$iSeries];
                  if ($auxMms->getPublicDate() > $auxSeries->getPublicDate() ) {
                      $return[] = $auxMms;
                      $iMms++;
                  } else {
                      $return[] = $auxSeries;
                      $iSeries++;
                  }
                  $i++;
              }
          }
          
          return $return;
      }
}