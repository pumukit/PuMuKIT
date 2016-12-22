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
        try {
            if ($multimediaObject->getProperty($key)) {
                $this->dm->createQueryBuilder('PumukitSchemaBundle:MultimediaObject')
                    ->update()
                    ->field('properties.'.$key)->push($value)
                    ->field('_id')->equals($multimediaObject->getId())
                    ->getQuery()
                    ->execute();
            } else {
                $this->dm->createQueryBuilder('PumukitSchemaBundle:MultimediaObject')
                    ->update()
                    ->field('properties.'.$key)->set(array($value))
                    ->field('_id')->equals($multimediaObject->getId())
                    ->getQuery()
                    ->execute();
            }
        } catch (\Exception $e) {
        }
    }

    private function delPropertyInArray(MultimediaObject $multimediaObject, $key, $value)
    {
        try {
            if (array($value) == $multimediaObject->getProperty($key)) {
                $this->dm->createQueryBuilder('PumukitSchemaBundle:MultimediaObject')
                    ->update()
                    ->field('properties.'.$key)->unsetField()
                    ->field('_id')->equals($multimediaObject->getId())
                    ->field('properties.'.$key)->equals($value)
                    ->getQuery()
                    ->execute();

                return true;
            } else {
                $out = $this->dm->createQueryBuilder('PumukitSchemaBundle:MultimediaObject')
                     ->update()
                     ->field('properties.'.$key)->pull($value)
                     ->field('_id')->equals($multimediaObject->getId())
                     ->field('properties.'.$key)->equals($value)
                     ->getQuery()
                     ->execute();

                return (isset($out['nModified']) && 1 == $out['nModified']) || (isset($out['n']) && 1 == $out['n']);
            }
        } catch (\Exception $e) {
        }

        return false;
    }
}
