<?php

declare(strict_types=1);

namespace App\MediaProcessing\Application\Job\Cancel;

use App\Shared\Domain\EventBusInterface;
use App\MediaProcessing\Domain\Event\JobCancelledEvent;
use App\MediaProcessing\Domain\Exception\JobNotFoundException;
use App\MediaProcessing\Domain\Repository\JobRepositoryInterface;
use Pumukit\EncoderBundle\Document\Job;

final class CancelJobService
{
    public function __construct(
        private readonly JobRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus
    ) {}

    public function __invoke(CancelJobRequest $request): CancelJobResponse
    {
        $job = $this->repository->find($request->id);

        if (!$job) {
            throw new JobNotFoundException($request->id);
        }

        $job->setStatus(Job::STATUS_PAUSED);

        $this->repository->save($job);

        $this->eventBus->dispatch(
            new JobCancelledEvent($job),
            JobCancelledEvent::NAME
        );

        return new CancelJobResponse($job);
    }
}
