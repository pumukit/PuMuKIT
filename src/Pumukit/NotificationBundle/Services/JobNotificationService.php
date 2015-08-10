<?php

namespace Pumukit\NotificationBundle\Services;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\JobService;

class JobNotificationService
{
    private $senderService;
    private $jobService;
    private $platformName;
    private $senderName;
    private $environment;
    private $translator;

    public function __construct(SenderService $senderService, JobService $jobService, TranslatorInterface $translator, $enable, $platformName, $senderName, $environment="dev")
    {
        $this->senderService = $senderService;
        $this->jobService = $jobService;
        $this->translator = $translator;
        $this->enable = $enable;
        $this->platformName = $platformName;
        $this->senderName = $senderName;
        $this->environment = $environment;
    }

    /**
     * On job success
     *
     * @param JobEvent $event
     */
    public function onJobSuccess(JobEvent $event)
    {
        if ($this->enable) {
            $job = $event->getJob();
            if (!$job) {
                return;
            }

            if (!$job->getEmail()) {
                return;
            }

            $successMessage = $this->translator->trans("Job with id '".$job->getId()."' successfully finished");
            $subject = ($this->platformName?$this->platformName.': ':'').$successMessage;
            $template = 'PumukitNotificationBundle:Email:job.html.twig';
            $parameters = array(
                                'subject' => $subject,
                                'job_status' => Job::$statusTexts[$job->getStatus()], 
                                'job' => $job,
                                'commandLine' => $this->jobService->renderBat($job),
                                'sender_name' => $this->senderName
                                );
            $output = $this->senderService->sendNotification($job->getEmail(), $subject, $template, $parameters, false);
            return $output;
        }
    }

    /**
     * On job error
     *
     * @param JobEvent $event
     */
    public function onJobError(JobEvent $event)
    {
        if ($this->enable) {
            $job = $event->getJob();
            if (!$job) {
                return;
            }

            if (!$job->getEmail()) {
                return;
            }

            $errorMessage = $this->translator->trans("Job with id '".$job->getId()."' failed");
            $subject = ($this->platformName?$this->platformName.': ':'').$errorMessage;
            $template = 'PumukitNotificationBundle:Email:job.html.twig';
            $parameters = array(
                                'subject' => $subject,
                                'job_status' => Job::$statusTexts[$job->getStatus()], 
                                'job' => $job,
                                'commandLine' => $this->jobService->renderBat($job),
                                'sender_name' => $this->senderName
                                );
            $output = $this->senderService->sendNotification($job->getEmail(), $subject, $template, $parameters, true);
            return $output;
        }
    }
}