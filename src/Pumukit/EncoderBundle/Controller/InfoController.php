<?php

namespace Pumukit\EncoderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;

/**
 * @Route("/admin/encoder")
 * @Security("is_granted('ROLE_ACCESS_JOBS')")
 */
class InfoController extends Controller
{
    /**
     * @Route("/", name="pumukit_encoder_info")
     * @Template("PumukitEncoderBundle:Info:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $user = $this->getUser();

        $cpuService = $this->get('pumukitencoder.cpu');
        $cpus = $cpuService->getCpus();

        $dm = $this->get('doctrine_mongodb')->getManager();
        $jobRepo = $dm->getRepository(Job::class);

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

        $jobService = $this->get('pumukitencoder.job');

        if (!$user->hasRole(PermissionProfile::SCOPE_PERSONAL)) {
            $stats = $jobService->getAllJobsStatus();
        } else {
            $stats = $jobService->getAllJobsStatusWithOwner($user);
        }

        $cpuService = $this->get('pumukitencoder.cpu');
        $deactivatedCpus = $cpuService->getCpuNamesInMaintenanceMode();

        return [
            'cpus' => $cpus,
            'deactivated_cpus' => $deactivatedCpus,
            'jobs' => [
                'pending' => [
                    'total' => ($stats['paused'] + $stats['waiting']),
                    'jobs' => $this->createPager($pendingJobs, $request->query->get('page_pending', 1)),
                ],
                'executing' => [
                    'total' => ($stats['executing']),
                    'jobs' => $this->createPager($executingJobs, $request->query->get('page_executing', 1), 20),
                ],
                'executed' => [
                    'total' => ($stats['error'] + $stats['finished']),
                    'jobs' => $this->createPager($executedJobs, $request->query->get('page_executed', 1)),
                ],
            ],
            'stats' => $stats,
        ];
    }

    private function createPager($objects, $page, $limit = 5)
    {
        $adapter = new DoctrineODMMongoDBAdapter($objects);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit)->setNormalizeOutOfRangePages(true)->setCurrentPage($page);

        return $pagerfanta;
    }

    /**
     * @Route("/job/{id}", methods="GET", name="pumukit_encoder_job")
     * @Template
     */
    public function infoJobAction(Job $job, Request $request)
    {
        $deletedMultimediaObject = false;
        try {
            $command = $this->get('pumukitencoder.job')->renderBat($job);
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
     * @Route("/job", methods="POST", name="pumukit_encoder_update_job")
     */
    public function updateJobPriorityAction(Request $request)
    {
        $priority = $request->get('priority');
        $jobId = $request->get('jobId');
        $this->get('pumukitencoder.job')->updateJobPriority($jobId, $priority);

        return new JsonResponse([
            'jobId' => $jobId,
            'priority' => $priority,
        ]);
    }

    /**
     * @Route("/job", methods="DELETE", name="pumukit_encoder_delete_job")
     */
    public function deleteJobAction(Request $request)
    {
        $jobId = $request->get('jobId');
        $this->get('pumukitencoder.job')->deleteJob($jobId);

        return new JsonResponse(['jobId' => $jobId]);
    }

    /**
     * @Route("/job/retry/{id}", methods="POST", name="pumukit_encoder_retry_job")
     */
    public function retryJobAction(Job $job, Request $request)
    {
        $flashMessage = $this->get('pumukitencoder.job')->retryJob($job);

        return new JsonResponse([
            'jobId' => $job->getId(),
            'mesage' => $flashMessage,
        ]);
    }

    /**
     * @Route("/mm/{id}", methods="GET", name="pumukit_encoder_mm")
     */
    public function multimediaObjectAction(MultimediaObject $multimediaObject, Request $request)
    {
        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_shortener', ['id' => $multimediaObject->getId()]));
    }
}
