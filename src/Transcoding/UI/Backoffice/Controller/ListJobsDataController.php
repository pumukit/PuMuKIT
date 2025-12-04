<?php

declare(strict_types=1);

namespace App\Transcoding\UI\Backoffice\Controller;

use App\Transcoding\Application\Job\List\ListJobsRequest;
use App\Transcoding\Application\Job\List\ListJobsService;
use App\Transcoding\UI\Backoffice\Presenter\JobDataTablePresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ListJobsDataController extends AbstractController
{
    public function __construct(
        private readonly ListJobsService $listJobsService,
        private readonly JobDataTablePresenter $presenter
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $offset = (int) $request->query->get('offset', 0);
        $limit = (int) $request->query->get('limit', 10);
        $sort = $request->query->get('sort', 'timeini');
        $order = $request->query->get('order', 'desc');

        $page = (int) floor($offset / $limit) + 1;

        $dto = new ListJobsRequest(
            page: $page,
            limit: $limit,
            sort: $sort,
            order: $order
        );

        $jobsResponse = ($this->listJobsService)($dto);

        $rows = [];
        foreach ($jobsResponse->jobs as $job) {
            $rows[] = $this->presenter->present($job);
        }

        return $this->json([
            'total' => $jobsResponse->total,
            'rows' => $rows,
        ]);
    }
}
