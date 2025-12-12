<?php

declare(strict_types=1);

namespace App\UI\Backoffice\MediaProcessing\Job\Presenter;

use App\ContentManagement\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;
use App\UI\Backoffice\ContentManagement\MultimediaObject\Helpers\TypeIcon;
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
            'mm_type' => TypeIcon::convert($multimediaObject->getType()),
            'profile' => $job->getProfile(),
            'status' => StatusIcon::convert($job->getStatus()).' '.$job->getStatusText(),
            'priority' => $job->getPriority(),
            'cpu' => $job->getCpu(),
            'duration' => CalcDuration::obtain($job->getTimeini(), $job->getTimeend()),
            'timeini' => DateFormat::formatComplete($job->getTimeini()),
            'actions' => $this->renderActions($job),
        ];
    }

    private function renderActions(Job $job): string
    {
        $viewButton = $this->twig->render('@Shared/Views/components/table/buttons/_view_button.html.twig', [
            'url' => $this->router->generate('media_processing_job_view', ['id' => $job->getId()]),
        ]);

        $stopButton = $this->twig->render('@Shared/Views/components/table/buttons/_custom_button.html.twig', [
            'url' => $this->router->generate('media_processing_job_cancel', ['id' => $job->getId()]),
            'icon' => 'fa-cancel',
            'style' => 'danger',
            'title' => 'Stop',
            'type' => 'form',
            'confirm' => 'Are you sure you want to stop this job?',
            'disabled' => !in_array($job->getStatus(), [Job::STATUS_EXECUTING, Job::STATUS_WAITING], true),
        ]);

        return $viewButton.$stopButton;
    }
}
