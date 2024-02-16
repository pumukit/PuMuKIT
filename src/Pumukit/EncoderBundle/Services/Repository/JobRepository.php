<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\Job;

final class JobRepository
{
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function getAllJobsStatus(): array
    {
        return [
            'paused' => $this->jobRepository()->countWithStatus([Job::STATUS_PAUSED]),
            'waiting' => $this->jobRepository()->countWithStatus([Job::STATUS_WAITING]),
            'executing' => $this->jobRepository()->countWithStatus([Job::STATUS_EXECUTING]),
            'finished' => $this->jobRepository()->countWithStatus([Job::STATUS_FINISHED]),
            'error' => $this->jobRepository()->countWithStatus([Job::STATUS_ERROR]),
        ];
    }

    public function getAllJobsStatusWithOwner($owner): array
    {
        return [
            'paused' => is_countable($this->jobRepository()->findWithStatusAndOwner([Job::STATUS_PAUSED], [], $owner)) ? count($this->jobRepository()->findWithStatusAndOwner([Job::STATUS_PAUSED], [], $owner)) : 0,
            'waiting' => is_countable($this->jobRepository()->findWithStatusAndOwner([Job::STATUS_WAITING], [], $owner)) ? count($this->jobRepository()->findWithStatusAndOwner([Job::STATUS_WAITING], [], $owner)) : 0,
            'executing' => is_countable($this->jobRepository()->findWithStatusAndOwner([Job::STATUS_EXECUTING], [], $owner)) ? count($this->jobRepository()->findWithStatusAndOwner([Job::STATUS_EXECUTING], [], $owner)) : 0,
            'finished' => is_countable($this->jobRepository()->findWithStatusAndOwner([Job::STATUS_FINISHED], [], $owner)) ? count($this->jobRepository()->findWithStatusAndOwner([Job::STATUS_FINISHED], [], $owner)) : 0,
            'error' => is_countable($this->jobRepository()->findWithStatusAndOwner([Job::STATUS_ERROR], [], $owner)) ? count($this->jobRepository()->findWithStatusAndOwner([Job::STATUS_ERROR], [], $owner)) : 0,
        ];
    }

    public function getNextJob()
    {
        return $this->jobRepository()->findHigherPriorityWithStatus([Job::STATUS_WAITING]);
    }

    public function getNotFinishedJobsByMultimediaObjectId($mmId)
    {
        return $this->jobRepository()->findNotFinishedByMultimediaObjectId($mmId);
    }

    private function jobRepository()
    {
        return $this->documentManager->getRepository(Job::class);
    }
}
