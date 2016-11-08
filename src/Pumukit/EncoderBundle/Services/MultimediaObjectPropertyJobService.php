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
        $this->save($multimediaObject);
    }

    public function executeJob(MultimediaObject $multimediaObject, Job $job)
    {
        if ($this->delPropertyInArray($multimediaObject, 'pending_jobs', $job->getId())) {
            $this->addPropertyInArray($multimediaObject, 'executing_jobs', $job->getId());
        }
        $this->save($multimediaObject);
    }

    public function finishJob(MultimediaObject $multimediaObject, Job $job)
    {
        if ($this->delPropertyInArray($multimediaObject, 'executing_jobs', $job->getId())) {
            $this->addPropertyInArray($multimediaObject, 'finished_jobs', $job->getId());
        }
        $this->save($multimediaObject);
    }

    public function errorJob(MultimediaObject $multimediaObject, Job $job)
    {
        if ($this->delPropertyInArray($multimediaObject, 'executing_jobs', $job->getId())) {
            $this->addPropertyInArray($multimediaObject, 'error_jobs', $job->getId());
        }
        $this->save($multimediaObject);
    }

    public function retryJob(MultimediaObject $multimediaObject, Job $job)
    {
        if ($this->delPropertyInArray($multimediaObject, 'error_jobs', $job->getId())) {
            $this->addPropertyInArray($multimediaObject, 'pending_jobs', $job->getId());
        }
        $this->save($multimediaObject);
    }

    private function save(MultimediaObject $multimediaObject)
    {
        $this->dm->persist($multimediaObject);
        $this->dm->flush();
    }

    private function addPropertyInArray(MultimediaObject $multimediaObject, $key, $value)
    {
        if ($values = $multimediaObject->getProperty($key)) {
            $values[] = $value;
            $multimediaObject->setProperty($key, $values);
        } else {
            $multimediaObject->setProperty($key, array($value));
        }
    }

    /**
     * @return true if remove correctly
     */
    private function delPropertyInArray(MultimediaObject $multimediaObject, $key, $value)
    {
        if ($values = $multimediaObject->getProperty($key)) {
            $values = array_values(array_diff($values, array($value)));
            if ($values) {
                $multimediaObject->setProperty($key, $values);
            } else {
                $multimediaObject->removeProperty($key);
            }
            return true;
        }
        return false;
    }
}
