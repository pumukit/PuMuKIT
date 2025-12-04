<?php

declare(strict_types=1);

namespace App\Transcoding\Application\Job\Cancel;

use App\Shared\Domain\EventBusInterface;
use App\Transcoding\Domain\Event\JobCancelledEvent;
use App\Transcoding\Domain\Exception\JobNotFoundException;
use App\Transcoding\Domain\Repository\JobRepositoryInterface;
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
