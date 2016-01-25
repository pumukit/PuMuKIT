<?php

namespace Pumukit\VideoEditorBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class AnnotationsAPIListenerService
{
    private $dm;
    private $repo;
    private $dispatcher;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repoMmobj = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->repoAnnotations = $this->dm->getRepository('PumukitSchemaBundle:Annotation');
    }

    public function onAnnotationsAPIUpdate($event)
    {
        $mmobjId = $event->getMultimediaObject();
        $mmobj = $this->repoMmobj->find($mmobjId);
        //get all annotations for this mmobj
        $annotations = $this->repoAnnotations->createQueryBuilder()->field('multimediaObject')->equals(new \MongoId($mmobjId))->getQuery()->execute()->toArray();
        $softDuration = $mmobj->getDuration();//init duration (in case there are no annotations
        $allAnnotations = array();
        //Prepares the allAnnotations structure we will use
        foreach ($annotations as $annon) {
            $allAnnotations[$annon->getType()] = json_decode($annon->getValue(), true);
        }
        $trimTimes = null;
        //If there is a trimming, change the original time by the trimming time
        if (isset($allAnnotations['paella/trimming']['trimming'])) {
            $trimTimes = $allAnnotations['paella/trimming']['trimming'];
            $softDuration = $trimTimes['end'] - $trimTimes['start'];
        }
        //If there are any breaks, arrange the breaks array so they don't overlap and decrease the total time by the sum of the breaks.
        if (isset($allAnnotations['paella/breaks']['breaks'])) {
            $allBreaks = $allAnnotations['paella/breaks']['breaks'];
            $allBreaks = $this->getProperBreaks($allBreaks, $trimTimes);
            foreach ($allBreaks as $break) {
                $breakTime = $break['e'] - $break['s'];
                $softDuration -= $breakTime;
            }
        }
        //Add to the mmobj as 'soft-editing-duration'
        $mmobj->setProperty('soft-editing-duration', $softDuration);
        $this->dm->persist($mmobj);
        $this->dm->flush();
    }

    private function getProperBreaks($breaks, $trim)
    {
        if (isset($trim)) {
            //Exclude breaks that aren't inside the trimming marks
            $breaks = array_filter($breaks, function ($a) use ($trim) {
                return $a['s'] <= $trim['end'] && $a['e'] >= $trim['start'];
            });
            //Cut breaks that are partially inside the trimming marks
            $breaks = array_map(function ($a) use ($trim) {
                if ($a['s'] < $trim['start']) {
                    $a['s'] = $trim['start'];
                }
                if ($a['e'] > $trim['end']) {
                    $a['e'] = $trim['end'];
                }

                return $a;
            }, $breaks);
        }
        //Sort breaks by their starting points
        usort($breaks, function ($a, $b) {
            return $a['s'] > $b['s'];
        });
        $allBreaks = array();
        //Join breaks that overlap
        foreach ($breaks as $brk) {
            if (!isset($temp)) {
                $temp = $brk;
            }
            if ($temp['e'] >= $brk['s']) {
                $temp['e'] = $brk['e'];
            } else {
                $allBreaks[] = $temp;
                $temp = $brk;
            }
        }
        $allBreaks[] = $temp;

        return $allBreaks;
    }
}
