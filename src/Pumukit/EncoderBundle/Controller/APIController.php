<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Controller;

use Pumukit\EncoderBundle\Services\CpuService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\EncoderBundle\Services\Repository\JobRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/encoder")
 *
 * @Security("is_granted('ROLE_ACCESS_API') and is_granted('ROLE_SCOPE_GLOBAL')")
 */
class APIController extends AbstractController
{
    private ProfileService $profileService;
    private CpuService $cpuService;
    private JobRepository $jobRepository;

    public function __construct(ProfileService $profileService, CpuService $cpuService, JobRepository $jobRepository)
    {
        $this->profileService = $profileService;
        $this->cpuService = $cpuService;
        $this->jobRepository = $jobRepository;
    }

    /**
     * @Route("/profiles.{_format}", defaults={"_format"="json"}, requirements={"_format"="json"})
     */
    public function profilesAction(): JsonResponse
    {
        $profiles = $this->profileService->getProfiles();

        return new JsonResponse($profiles);
    }

    /**
     * @Route("/cpus.{_format}", defaults={"_format"="json"}, requirements={"_format"="json"})
     */
    public function cpusAction(): JsonResponse
    {
        $cpus = $this->cpuService->getCpus();

        return new JsonResponse($cpus);
    }

    /**
     * @Route("/jobs.{_format}", defaults={"_format"="json"}, requirements={"_format"="json"})
     */
    public function jobsAction(): JsonResponse
    {
        $stats = $this->jobRepository->getAllJobsStatus();

        return new JsonResponse($stats);
    }
}
