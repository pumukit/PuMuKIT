<?php

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * Update the multimedia object properties with info of the jobs.
 */
class MultimediaObjectPropertyJobService
{
    private $dm;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    public function addJob(MultimediaObject $multimediaObject, Job $job)
    {
        $this->addPropertyInArray($multimediaObject, 'pending_jobs', $job->getId());
    }

    public function executeJob(MultimediaObject $multimediaObject, Job $job)
    {
        if ($this->delPropertyInArray($multimediaObject, 'pending_jobs', $job->getId())) {
            $this->addPropertyInArray($multimediaObject, 'executing_jobs', $job->getId());
        }
    }

    public function finishJob(MultimediaObject $multimediaObject, Job $job)
    {
        if ($this->delPropertyInArray($multimediaObject, 'executing_jobs', $job->getId())) {
            $this->addPropertyInArray($multimediaObject, 'finished_jobs', $job->getId());
        }
    }

    public function errorJob(MultimediaObject $multimediaObject, Job $job)
    {
        if ($this->delPropertyInArray($multimediaObject, 'executing_jobs', $job->getId())) {
            $this->addPropertyInArray($multimediaObject, 'error_jobs', $job->getId());
        }
    }

    public function retryJob(MultimediaObject $multimediaObject, Job $job)
    {
        if ($this->delPropertyInArray($multimediaObject, 'error_jobs', $job->getId())) {
            $this->addPropertyInArray($multimediaObject, 'pending_jobs', $job->getId());
        }
    }

    private function addPropertyInArray(MultimediaObject $multimediaObject, $key, $value)
    {
        $this->dm->createQueryBuilder(MultimediaObject::class)
            ->update()
            ->field('properties.'.$key)->push($value)
            ->field('_id')->equals($multimediaObject->getId())
            ->getQuery()
            ->execute()
        ;
    }

    private function delPropertyInArray(MultimediaObject $multimediaObject, $key, $value)
    {
        //Try to delete all the property if is the last job in this state.
        $out = $this->dm->createQueryBuilder(MultimediaObject::class)
            ->update()
            ->field('properties.'.$key)->unsetField()
            ->field('_id')->equals($multimediaObject->getId())
            ->field('properties.'.$key)->equals([$value])
            ->getQuery()
            ->execute()
        ;

        if ((isset($out['nModified']) && 1 == $out['nModified']) || (isset($out['n']) && 1 == $out['n'])) {
            return true;
        }

        // If not delete job from the property
        $out = $this->dm->createQueryBuilder(MultimediaObject::class)
            ->update()
            ->field('properties.'.$key)->pull($value)
            ->field('_id')->equals($multimediaObject->getId())
            ->field('properties.'.$key)->equals($value)
            ->getQuery()
            ->execute()
        ;

        if ((isset($out['nModified']) && 1 == $out['nModified']) || (isset($out['n']) && 1 == $out['n'])) {
            return true;
        }

        return false;
    }
}
