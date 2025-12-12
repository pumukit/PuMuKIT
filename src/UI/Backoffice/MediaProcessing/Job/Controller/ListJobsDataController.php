<?php

declare(strict_types=1);

namespace App\UI\Backoffice\MediaProcessing\Job\Controller;

use App\MediaProcessing\Job\Application\List\ListJobsRequest;
use App\MediaProcessing\Job\Application\List\ListJobsService;
use App\UI\Backoffice\MediaProcessing\Job\Presenter\JobDataTablePresenter;
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
        $page = (int) $request->query->get('page', '1');
        $limit = (int) $request->query->get('limit', '10');
        $sort = $request->query->get('sort', 'timeini');
        $order = $request->query->get('order', 'desc');


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
