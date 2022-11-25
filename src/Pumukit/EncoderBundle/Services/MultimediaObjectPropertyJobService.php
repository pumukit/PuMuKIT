<?php

declare(strict_types=1);

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

    public function addJob(MultimediaObject $multimediaObject, Job $job): void
    {
        $this->addPropertyInArray($multimediaObject, 'pending_jobs', $job->getId());
    }

    public function setJobAsExecuting(MultimediaObject $multimediaObject, Job $job): void
    {
        if ($this->delPropertyInArray($multimediaObject, 'pending_jobs', $job->getId())) {
            $this->addPropertyInArray($multimediaObject, 'executing_jobs', $job->getId());
        }
    }

    public function finishJob(MultimediaObject $multimediaObject, Job $job): void
    {
        if ($this->delPropertyInArray($multimediaObject, 'executing_jobs', $job->getId())) {
            $this->addPropertyInArray($multimediaObject, 'finished_jobs', $job->getId());
        }
    }

    public function errorJob(MultimediaObject $multimediaObject, Job $job): void
    {
        if ($this->delPropertyInArray($multimediaObject, 'executing_jobs', $job->getId())) {
            $this->addPropertyInArray($multimediaObject, 'error_jobs', $job->getId());
        }
    }

    public function retryJob(MultimediaObject $multimediaObject, Job $job): void
    {
        if ($this->delPropertyInArray($multimediaObject, 'error_jobs', $job->getId())) {
            $this->addPropertyInArray($multimediaObject, 'pending_jobs', $job->getId());
        }
    }

    private function addPropertyInArray(MultimediaObject $multimediaObject, string $key, string $value): void
    {
        $propertyData = $multimediaObject->getProperty($key);
        $propertyData[] = $value;
        $multimediaObject->setProperty($key, $propertyData);
        $this->dm->flush();
    }

    private function delPropertyInArray(MultimediaObject $multimediaObject, string $key, string $value): bool
    {
        if (($propertyValue = $multimediaObject->getProperty($key))) {
            $positionValue = array_search($value, $propertyValue, true);
            if (false !== $positionValue) {
                unset($propertyValue[$positionValue]);
                if (0 === (is_countable($propertyValue) ? count($propertyValue) : 0)) {
                    $multimediaObject->removeProperty($key);
                } else {
                    $multimediaObject->setProperty($key, array_values($propertyValue));
                }
                $this->dm->flush();

                return true;
            }
        }

        return false;
    }
}
