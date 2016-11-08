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

/**
 * @Route("/admin/encoder")
 * @Security("is_granted('ROLE_ACCESS_JOBS')")
 */
class InfoController extends Controller
{
    /**
     * @Route("/", name="pumukit_encoder_info")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $cpuService = $this->get('pumukitencoder.cpu');
        $cpus = $cpuService->getCpus();

        $dm = $this->get('doctrine_mongodb')->getManager();
        $jobRepo = $dm->getRepository('PumukitEncoderBundle:Job');

        $pendingStates = array();
        if ($request->query->get('show_waiting', true)) {
            $pendingStates[] = Job::STATUS_WAITING;
        }
        if ($request->query->get('show_paused', true)) {
            $pendingStates[] = Job::STATUS_PAUSED;
        }
        $pendingSort = array('priority' => 'desc', 'timeini' => 'asc');
        $pendingJobs = $jobRepo->createQueryWithStatus($pendingStates, $pendingSort);
        $executingSort = array('timestart' => 'desc');
        $executingJobs = $jobRepo->createQueryWithStatus(array(Job::STATUS_EXECUTING), $executingSort);
        $pendingStates = array();
        if ($request->query->get('show_error', true)) {
            $pendingStates[] = Job::STATUS_ERROR;
        }
        if ($request->query->get('show_finished', false)) {
            $pendingStates[] = Job::STATUS_FINISHED;
        }
        $executedSort = array('timeend' => 'desc');
        $executedJobs = $jobRepo->createQueryWithStatus($pendingStates, $executedSort);

        $jobService = $this->get('pumukitencoder.job');
        $stats = $jobService->getAllJobsStatus();
        $cpuService = $this->get('pumukitencoder.cpu');
        $deactivatedCpus = $cpuService->getCpuNamesInMaintenanceMode();
        return array('cpus' => $cpus,
                     'deactivated_cpus' => $deactivatedCpus,
                     'jobs' => array('pending' =>   array('total' => ($stats['paused'] + $stats['waiting']),
                                                          'jobs' => $this->createPager($pendingJobs, $request->query->get('page_pending', 1))),
                                     'executing' => array('total' => ($stats['executing']),
                                                          'jobs' => $this->createPager($executingJobs, $request->query->get('page_executing', 1), 20)),
                                     'executed' =>  array('total' => ($stats['error'] + $stats['finished']),
                                                          'jobs' => $this->createPager($executedJobs, $request->query->get('page_executed', 1)))),
                     'stats' => $stats);
    }

    private function createPager($objects, $page, $limit = 5)
    {
        $adapter = new DoctrineODMMongoDBAdapter($objects);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit)
            ->setNormalizeOutOfRangePages(true)
            ->setCurrentPage($page);

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
        return array('job' => $job, 'command' => $command, 'deletedMultimediaObject' => $deletedMultimediaObject);
    }

    /**
     * @Route("/job", methods="POST", name="pumukit_encoder_update_job")
     */
    public function updateJobPriorityAction(Request $request)
    {
        $priority = $request->get('priority');
        $jobId = $request->get('jobId');
        $this->get('pumukitencoder.job')->updateJobPriority($jobId, $priority);

        return new JsonResponse(array("jobId" => $jobId, "priority" => $priority));
    }

    /**
     * @Route("/job", methods="DELETE", name="pumukit_encoder_delete_job")
     */
    public function deleteJobAction(Request $request)
    {
        $jobId = $request->get('jobId');
        $this->get('pumukitencoder.job')->deleteJob($jobId);

        return new JsonResponse(array("jobId" => $jobId));
    }

    /**
     * @Route("/job/retry/{id}", methods="POST", name="pumukit_encoder_retry_job")
     */
    public function retryJobAction(Job $job, Request $request)
    {
        $flashMessage = $this->get('pumukitencoder.job')->retryJob($job);

        return new JsonResponse(array("jobId" => $job->getId(), 'mesage' => $flashMessage));
    }

    /**
     * @Route("/mm/{id}", methods="GET", name="pumukit_encoder_mm")
     */
    public function multimediaObjectAction(MultimediaObject $multimediaObject, Request $request)
    {
        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_shortener', array('id' => $multimediaObject->getId())));
    }
}
