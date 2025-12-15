<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Infrastructure\Ui\Backoffice\Http\Controller;

use App\MediaProcessing\Job\Application\List\ListJobsRequest;
use App\MediaProcessing\Job\Application\List\ListJobsService;
use App\MediaProcessing\Job\Infrastructure\Ui\Backoffice\Http\Presenter\JobDataTablePresenter;
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
