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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/encoder")
 *
 * @Security("is_granted('ROLE_ACCESS_JOBS')")
 */
class InfoController extends AbstractController
{
    private JobRender $jobRender;
    private JobRepository $jobRepository;
    private JobUpdater $jobUpdater;
    private JobRemover $jobRemover;

    public function __construct(JobRender $jobRender, JobRepository $jobRepository, JobUpdater $jobUpdater, JobRemover $jobRemover)
    {
        $this->jobRender = $jobRender;
        $this->jobRepository = $jobRepository;
        $this->jobUpdater = $jobUpdater;
        $this->jobRemover = $jobRemover;
    }

    /**
     * @Route("/", name="pumukit_encoder_info")
     *
     * @Template("@PumukitEncoder/Info/index.html.twig")
     */
    public function indexAction(Request $request, DocumentManager $documentManager, CpuService $cpuService, PaginationService $paginationService): array
    {
        $user = $this->getUser();
        $cpus = $cpuService->getCpus();
        $jobRepo = $documentManager->getRepository(Job::class);

        $pendingStates = [];
        if ($request->query->get('show_waiting', true)) {
            $pendingStates[] = Job::STATUS_WAITING;
        }
        if ($request->query->get('show_paused', true)) {
            $pendingStates[] = Job::STATUS_PAUSED;
        }

        $pendingSort = [
            'priority' => 'desc',
            'timeini' => 'asc',
        ];

        if (!$user->hasRole(PermissionProfile::SCOPE_PERSONAL)) {
            $pendingJobs = $jobRepo->createQueryWithStatus($pendingStates, $pendingSort);
        } else {
            $pendingJobs = $jobRepo->createQueryWithStatusAndOwner($pendingStates, $pendingSort, $user);
        }

        $executingSort = ['timestart' => 'desc'];
        if (!$user->hasRole(PermissionProfile::SCOPE_PERSONAL)) {
            $executingJobs = $jobRepo->createQueryWithStatus([Job::STATUS_EXECUTING], $executingSort);
        } else {
            $executingJobs = $jobRepo->createQueryWithStatusAndOwner([Job::STATUS_EXECUTING], $executingSort, $user);
        }

        $pendingStates = [];
        if ($request->query->get('show_error', true)) {
            $pendingStates[] = Job::STATUS_ERROR;
        }
        if ($request->query->get('show_finished', false)) {
            $pendingStates[] = Job::STATUS_FINISHED;
        }
        $executedSort = ['timeend' => 'desc'];

        if (!$user->hasRole(PermissionProfile::SCOPE_PERSONAL)) {
            $executedJobs = $jobRepo->createQueryWithStatus($pendingStates, $executedSort);
        } else {
            $executedJobs = $jobRepo->createQueryWithStatusAndOwner($pendingStates, $executedSort, $user);
        }

        if (!$user->hasRole(PermissionProfile::SCOPE_PERSONAL)) {
            $stats = $this->jobRepository->getAllJobsStatus();
        } else {
            $stats = $this->jobRepository->getAllJobsStatusWithOwner($user);
        }

        $deactivatedCpus = $cpuService->getCpuNamesInMaintenanceMode();

        return [
            'cpus' => $cpus,
            'deactivated_cpus' => $deactivatedCpus,
            'jobs' => [
                'pending' => [
                    'total' => ($stats['paused'] + $stats['waiting']),
                    'jobs' => $this->createPager($paginationService, $pendingJobs, $request->query->get('page_pending', 1)),
                ],
                'executing' => [
                    'total' => $stats['executing'],
                    'jobs' => $this->createPager($paginationService, $executingJobs, $request->query->get('page_executing', 1), 20),
                ],
                'executed' => [
                    'total' => ($stats['error'] + $stats['finished']),
                    'jobs' => $this->createPager($paginationService, $executedJobs, $request->query->get('page_executed', 1)),
                ],
            ],
            'stats' => $stats,
        ];
    }

    /**
     * @Route("/job/{id}", methods={"GET"}, name="pumukit_encoder_job")
     *
     * @Template("@PumukitEncoder/Info/infoJob.html.twig")
     */
    public function infoJobAction(Request $request, Job $job): array
    {
        $deletedMultimediaObject = false;

        try {
            $command = $this->jobRender->renderBat($job);
        } catch (\Exception $e) {
            $command = $e->getMessage();
            $deletedMultimediaObject = true;
        }

        return [
            'job' => $job,
            'command' => $command,
            'deletedMultimediaObject' => $deletedMultimediaObject,
        ];
    }

    /**
     * @Route("/job", methods={"POST"}, name="pumukit_encoder_update_job")
     */
    public function updateJobPriorityAction(Request $request): JsonResponse
    {
        $priority = (int) $request->get('priority');
        $jobId = $request->get('jobId');
        $job = $this->jobRepository->searchJob($jobId);
        $this->jobUpdater->updateJobPriority($job, $priority);

        return new JsonResponse([
            'jobId' => $jobId,
            'priority' => $priority,
        ]);
    }

    /**
     * @Route("/error/job", methods={"POST"}, name="pumukit_encoder_error_job")
     */
    public function setAsFailedAction(Request $request): JsonResponse
    {
        $job = $this->jobRepository->searchJob($request->get('jobId'));
        $this->jobUpdater->errorJob($job);

        return new JsonResponse(['jobId' => $job->getId()]);
    }

    /**
     * @Route("/job", methods={"DELETE"}, name="pumukit_encoder_delete_job")
     */
    public function deleteJobAction(Request $request): JsonResponse
    {
        $job = $this->jobRepository->searchJob($request->get('jobId'));
        $this->jobRemover->delete($job);

        return new JsonResponse(['jobId' => $job->getId()]);
    }

    /**
     * @Route("/job/retry/{id}", methods={"POST"}, name="pumukit_encoder_retry_job")
     */
    public function retryJobAction(Job $job): JsonResponse
    {
        $flashMessage = $this->jobUpdater->retryJob($job);

        return new JsonResponse([
            'jobId' => $job->getId(),
            'mesage' => $flashMessage,
        ]);
    }

    /**
     * @Route("/mm/{id}", methods={"GET"}, name="pumukit_encoder_mm")
     */
    public function multimediaObjectAction(MultimediaObject $multimediaObject): RedirectResponse
    {
        return $this->redirectToRoute('pumukitnewadmin_mms_shortener', ['id' => $multimediaObject->getId()]);
    }

    private function createPager(PaginationService $paginationService, $objects, $page, $limit = 5)
    {
        return $paginationService->createDoctrineODMMongoDBAdapter($objects, (int) $page, (int) $limit);
    }
}
