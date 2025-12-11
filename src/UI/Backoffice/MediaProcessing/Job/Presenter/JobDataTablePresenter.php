<?php

declare(strict_types=1);

namespace App\UI\Backoffice\MediaProcessing\Job\Presenter;

use App\ContentManagement\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;
use App\UI\Backoffice\MediaProcessing\Job\Helpers\CalcDuration;
use App\UI\Backoffice\MediaProcessing\Job\Helpers\StatusIcon;
use App\UI\Backoffice\Shared\Helpers\DateFormat;
use App\UI\Backoffice\Shared\Helpers\LinkFormat;
use App\UI\Backoffice\Shared\Helpers\TextTruncate;
use Pumukit\EncoderBundle\Document\Job;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class JobDataTablePresenter
{
    public function __construct(
        private MultimediaObjectRepositoryInterface $multimediaObjectRepository,
        private RouterInterface $router,
        private Environment $twig,
    ) {}

    public function present(Job $job): array
    {
        $multimediaObject = $this->multimediaObjectRepository->findById($job->getMmid());
        return [
            'id' => $job->getId(),
            'mm_id' => LinkFormat::generate($this->router->generate('multimedia_object_view', ['id' => $job->getMmId(), 'tab' => 'media']), TextTruncate::long($multimediaObject->getTitle())),
            'profile' => $job->getProfile(),
            'status' => StatusIcon::convert($job->getStatus()) . ' ' . $job->getStatusText(),
            'priority' => $job->getPriority(),
            'cpu' => $job->getCpu(),
            'duration' => CalcDuration::obtain($job->getTimeini(), $job->getTimeend()) ?? '---',
            'timeini' => DateFormat::formatComplete($job->getTimeini()) ?? '---',
            'actions' => $this->renderActions($job),
        ];
    }

    private function renderActions(Job $job): string
    {
        return $this->twig->render('@Shared/Views/components/table/_datatable_actions.html.twig', [
            'actions' => [
                [
                    'type' => 'link',
                    'url' => $this->router->generate('media_processing_job_view', ['id' => $job->getId()]),
                    'style' => 'info',
                    'icon' => 'eye',
                    'title' => 'View',
                ],
                [
                    'type' => 'form',
                    'url' => '#',
                    'style' => 'danger',
                    'icon' => 'cancel',
                    'title' => 'Stop',
                    'confirm' => 'Are you sure you want to stop this job?',
                    'disabled' => !in_array($job->getStatus(), [Job::STATUS_EXECUTING, Job::STATUS_WAITING], true),
                ],
            ],
        ]);
    }
}
