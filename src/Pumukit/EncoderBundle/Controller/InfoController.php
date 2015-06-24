<?php

namespace Pumukit\EncoderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\EncoderBundle\Document\Job;

/**
 * @Route("/admin")
 */
class InfoController extends Controller
{
    /**
     * @Route("/encoder", name="pumukit_encoder_info")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $cpuService = $this->get('pumukitencoder.cpu');
        $cpus = $cpuService->getCpus();

        $dm = $this->get('doctrine_mongodb')->getManager();
        $jobRepo = $dm->getRepository('PumukitEncoderBundle:Job');

        $pendingStates = array();
        if ($request->query->get('show_waiting', true)) $pendingStates[] = Job::STATUS_WAITING;
        if ($request->query->get('show_paused', true)) $pendingStates[] = Job::STATUS_PAUSED;
        $pendingJobs = $jobRepo->createQueryWithStatus($pendingStates, true);
        $executingJobs = $jobRepo->createQueryWithStatus(array(Job::STATUS_EXECUTING));
        $pendingStates = array();
        if ($request->query->get('show_error', true)) $pendingStates[] = Job::STATUS_ERROR;
        if ($request->query->get('show_finished', false)) $pendingStates[] = Job::STATUS_FINISHED;
        $executedJobs = $jobRepo->createQueryWithStatus($pendingStates);

        $jobService = $this->get('pumukitencoder.job');
        $stats = $jobService->getAllJobsStatus();

        return array('cpus' => $cpus,
                     'jobs' => array('pending' =>   array('total' => ($stats['paused'] + $stats['waiting']),
                                                          'jobs' => $this->createPager($pendingJobs, $request->query->get('page_pending',1))),
                                     'executing' => array('total' => ($stats['executing']),
                                                          'jobs' => $this->createPager($executingJobs, $request->query->get('page_executing',1), 20)),
                                     'executed' =>  array('total' => ($stats['error'] + $stats['finished']),
                                                          'jobs' => $this->createPager($executedJobs, $request->query->get('page_executed',1)))),
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
     * @Route("/encoder/job/{id}", methods="GET", name="pumukit_encoder_job")
     * @Template
     */
    public function infoJobAction(Job $job, Request $request)
    {
        $command = $this->get('pumukitencoder.job')->renderBat($job);
        return array('job' => $job, 'command' => $command);
    }

    /**
     * @Route("/encoder/job", methods="POST", name="pumukit_encoder_update_job")
     */    
    public function updateJobPriorityAction(Request $request)
    {
        $priority = $request->get('priority');
        $jobId = $request->get('jobId');
        $this->get('pumukitencoder.job')->updateJobPriority($jobId, $priority);
        
        return new JsonResponse(array("jobId" => $jobId, "priority" => $priority));
    }

    /**
     * @Route("/encoder/job", methods="DELETE", name="pumukit_encoder_delete_job")
     */        
    public function deleteJobAction(Request $request)
    {
        $jobId = $request->get('jobId');
        $this->get('pumukitencoder.job')->deleteJob($jobId);

        return new JsonResponse(array("jobId" => $jobId));        
    }

    /**
     * @Route("/encoder/job/retry/{id}", methods="POST", name="pumukit_encoder_retry_job")
     */
    public function retryJobAction(Job $job, Request $request)
    {
        $flashMessage = $this->get('pumukitencoder.job')->retryJob($job);

        return new JsonResponse(array("jobId" => $job.getId(), 'mesage' => $flashMessage));
    }
}
