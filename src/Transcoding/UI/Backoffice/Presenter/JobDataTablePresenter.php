<?php

declare(strict_types=1);

namespace App\Transcoding\UI\Backoffice\Presenter;

use App\Shared\UI\Backoffice\Helpers\DateFormat;
use Pumukit\EncoderBundle\Document\Job;
use Symfony\Component\Routing\RouterInterface;

final class JobDataTablePresenter
{
    public function __construct(private readonly RouterInterface $router) {}

    public function present(Job $job): array
    {
        return [
            'id' => $job->getId(),
            'mm_id' => $job->getMmId() ?? '-',
            'profile' => $job->getProfile() ?? '-',
            'status' => $job->getStatus(),
            'status_text' => $this->renderStatusBadge($job->getStatus(), $job->getStatusText()),
            'priority' => $job->getPriority() ?? 0,
            'cpu' => $job->getCpu() ?? '-',
            'timeini' => DateFormat::formatComplete($job->getTimeini()) ?? '-',
            'timestart' => DateFormat::formatComplete($job->getTimestart()) ?? '-',
            'timeend' => DateFormat::formatComplete($job->getTimeend()) ?? '-',
            'actions' => $this->renderActions($job),
        ];
    }

    private function renderActions(Job $job): string
    {
        $viewUrl = $this->router->generate('transcoding_job_view', ['id' => $job->getId()]);

        $actions = sprintf(
            '<a href="%s" class="btn btn-sm btn-info" title="Ver"><i class="fa fa-eye"></i> View</a>',
            $viewUrl
        );

        if (in_array($job->getStatus(), [Job::STATUS_PAUSED, Job::STATUS_WAITING, Job::STATUS_EXECUTING])) {
            $actions .= sprintf(
                ' <button class="btn btn-sm btn-warning" onclick="cancelJob(\'%s\')" title="Cancelar"><i class="fa fa-stop"></i></button>',
                $job->getId()
            );
        }

        return '<div class="d-flex gap-1 justify-content-end">'.$actions.'</div>';
    }

    private function renderStatusBadge(int $status, string $statusText): string
    {
        $badgeClass = match ($status) {
            Job::STATUS_FINISHED => 'success',
            Job::STATUS_ERROR => 'danger',
            Job::STATUS_EXECUTING => 'info',
            Job::STATUS_WAITING => 'warning',
            Job::STATUS_PAUSED => 'secondary',
            default => 'secondary',
        };

        return sprintf('<span class="badge bg-%s">%s</span>', $badgeClass, $statusText);
    }
}
