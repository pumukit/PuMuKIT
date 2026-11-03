<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\CpuService;
use Pumukit\EncoderBundle\Services\JobRemover;
use Pumukit\EncoderBundle\Services\JobRender;
use Pumukit\EncoderBundle\Services\JobUpdater;
use Pumukit\EncoderBundle\Services\Repository\JobRepository;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/encoder')]
#[IsGranted('ROLE_ACCESS_JOBS')]
class InfoController extends AbstractController
{
    public const CSRF_TOKEN = 'encoder_job';
    public const BLOCKS = ['stats', 'pending', 'executing', 'executed', 'cpus'];

    public function __construct(
        private readonly JobRender $jobRender,
        private readonly JobRepository $jobRepository,
        private readonly JobUpdater $jobUpdater,
        private readonly JobRemover $jobRemover,
        private readonly DocumentManager $documentManager,
        private readonly CpuService $cpuService,
        private readonly PaginationService $paginationService,
    ) {}

    #[Route('/', name: 'pumukit_encoder_info')]
    public function indexAction(Request $request): Response
    {
        return $this->render('@PumukitEncoder/Info/index.html.twig', $this->blocks(self::BLOCKS, $request));
    }

    #[Route('/block/{block}', name: 'pumukit_encoder_info_block', methods: ['GET'])]
    public function blockAction(Request $request, string $block): Response
    {
        if (!in_array($block, self::BLOCKS, true)) {
            throw $this->createNotFoundException();
        }
        $parameters = $this->blocks([$block], $request);
        $response = $this->render('@PumukitEncoder/Info/block.html.twig', ['block' => $block] + $parameters);
        $response->headers->set('X-Encoder-Busy', $parameters['busy'] ? '1' : '0');

        return $response;
    }

    #[Route('/job/{id}', methods: ['GET'], name: 'pumukit_encoder_job')]
    #[Template('@PumukitEncoder/Info/infoJob.html.twig')]
    public function infoJobAction(Job $job): array
    {
        $this->denyUnlessOwner($job);
        $deletedMultimediaObject = false;

        try {
            $command = $this->jobRender->renderBat($job);
        } catch (\Throwable $e) {
            $command = $e->getMessage();
            $deletedMultimediaObject = true;
        }

        return [
            'job' => $job,
            'command' => $command,
            'deletedMultimediaObject' => $deletedMultimediaObject,
        ];
    }

    #[Route('/job', methods: ['POST'], name: 'pumukit_encoder_update_job')]
    public function updateJobPriorityAction(Request $request): JsonResponse
    {
        $job = $this->actionJob($request);
        $priority = max(1, min(3, $request->request->getInt('priority')));
        $this->jobUpdater->updateJobPriority($job, $priority);

        return new JsonResponse(['jobId' => $job->getId(), 'priority' => $priority]);
    }

    #[Route('/error/job', methods: ['POST'], name: 'pumukit_encoder_error_job')]
    public function setAsFailedAction(Request $request): JsonResponse
    {
        $job = $this->actionJob($request);
        $this->jobUpdater->errorJob($job);

        return new JsonResponse(['jobId' => $job->getId()]);
    }

    #[Route('/job', methods: ['DELETE'], name: 'pumukit_encoder_delete_job')]
    public function deleteJobAction(Request $request): JsonResponse
    {
        $job = $this->actionJob($request);
        $this->jobRemover->delete($job);

        return new JsonResponse(['jobId' => $job->getId()]);
    }

    #[Route('/job/retry/{id}', methods: ['POST'], name: 'pumukit_encoder_retry_job')]
    public function retryJobAction(Request $request, Job $job): JsonResponse
    {
        $this->denyUnlessValidToken($request);
        $this->denyUnlessOwner($job);
        $flashMessage = $this->jobUpdater->retryJob($job);

        return new JsonResponse(['jobId' => $job->getId(), 'mesage' => $flashMessage]);
    }

    #[Route('/mm/{id}', methods: ['GET'], name: 'pumukit_encoder_mm')]
    public function multimediaObjectAction(MultimediaObject $multimediaObject): RedirectResponse
    {
        return $this->redirectToRoute('pumukitnewadmin_mms_shortener', ['id' => $multimediaObject->getId()]);
    }

    private function blocks(array $blocks, Request $request): array
    {
        $owner = $this->personalUser();
        $stats = $owner ? $this->jobRepository->getAllJobsStatusWithOwner($owner) : $this->jobRepository->getAllJobsStatus();
        $parameters = [
            'stats' => $stats,
            'busy' => $stats['executing'] + $stats['waiting'] > 0,
            'jobs' => [],
        ];

        $lists = [
            'pending' => [
                [Job::STATUS_WAITING => $request->query->getBoolean('show_waiting', true), Job::STATUS_PAUSED => $request->query->getBoolean('show_paused', true)],
                ['priority' => 'desc', 'timeini' => 'asc'],
                $stats['waiting'] + $stats['paused'],
                5,
            ],
            'executing' => [[Job::STATUS_EXECUTING => true], ['timestart' => 'desc'], $stats['executing'], 20],
            'executed' => [
                [Job::STATUS_ERROR => $request->query->getBoolean('show_error', true), Job::STATUS_FINISHED => $request->query->getBoolean('show_finished', false)],
                ['timeend' => 'desc'],
                $stats['error'] + $stats['finished'],
                5,
            ],
        ];
        foreach (array_intersect_key($lists, array_flip($blocks)) as $name => [$states, $sort, $total, $limit]) {
            $parameters['jobs'][$name] = [
                'total' => $total,
                'jobs' => $this->paginationService->createDoctrineODMMongoDBAdapter(
                    $this->query(array_keys(array_filter($states)), $sort, $owner),
                    max(1, $request->query->getInt('page_'.$name, 1)),
                    $limit
                ),
            ];
        }

        if (in_array('cpus', $blocks, true)) {
            $parameters['cpus'] = $this->cpuService->getCpus();
            $parameters['deactivated_cpus'] = $this->cpuService->getCpuNamesInMaintenanceMode();
        }

        return $parameters;
    }

    private function query(array $states, array $sort, ?User $owner)
    {
        $repository = $this->documentManager->getRepository(Job::class);

        return $owner ? $repository->createQueryWithStatusAndOwner($states, $sort, $owner) : $repository->createQueryWithStatus($states, $sort);
    }

    private function personalUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User && $user->hasRole(PermissionProfile::SCOPE_PERSONAL) ? $user : null;
    }

    private function actionJob(Request $request): Job
    {
        $this->denyUnlessValidToken($request);
        $jobId = $request->request->getString('jobId');
        $job = preg_match('/^[a-f\d]{24}$/i', $jobId) ? $this->jobRepository->searchJob($jobId) : null;
        if (!$job instanceof Job) {
            throw $this->createNotFoundException();
        }
        $this->denyUnlessOwner($job);

        return $job;
    }

    private function denyUnlessValidToken(Request $request): void
    {
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
    }

    private function denyUnlessOwner(Job $job): void
    {
        $owner = $this->personalUser();
        if ($owner && $job->getEmail() !== $owner->getEmail()) {
            throw $this->createAccessDeniedException();
        }
    }
}
