<?php

namespace Pumukit\NotificationBundle\Services;

use Symfony\Component\Translation\TranslatorInterface;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\JobService;
use Symfony\Component\Routing\RouterInterface;

class JobNotificationService
{
    private $senderService;
    private $jobService;
    private $platformName;
    private $senderName;
    private $environment;
    private $translator;
    private $router;

    public function __construct(SenderService $senderService, JobService $jobService, TranslatorInterface $translator, RouterInterface $router, $enable, $platformName, $senderName, $environment, $template, $subjectSuccess, $subjectFails)
    {
        $this->senderService = $senderService;
        $this->jobService = $jobService;
        $this->translator = $translator;
        $this->router = $router;
        $this->enable = $enable;
        $this->platformName = $platformName;
        $this->senderName = $senderName;
        $this->environment = $environment;
        $this->template = $template;
        $this->subjectSuccess = $subjectSuccess;
        $this->subjectFails = $subjectFails;
    }

    /**
     * On job success.
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

            $multimediaObject = $event->getMultimediaObject();
            $multimediaObjectAdminLink = $this->getMultimediaObjectAdminLink($multimediaObject, $job->getMmId());

            $successMessage = $this->subjectSuccess;
            $subject = ($this->platformName ? $this->platformName.': ' : '').$successMessage;
            $template = $this->template;
            $parameters = array(
                                'subject' => $subject,
                                'job_status' => Job::$statusTexts[$job->getStatus()],
                                'job' => $job,
                                'commandLine' => $this->jobService->renderBat($job),
                                'sender_name' => $this->senderName,
                                'multimedia_object_admin_link' => $multimediaObjectAdminLink,
                                );
            $output = $this->senderService->sendNotification($job->getEmail(), $subject, $template, $parameters, false);

            return $output;
        }
    }

    /**
     * On job error.
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

            $multimediaObject = $event->getMultimediaObject();
            $multimediaObjectAdminLink = $this->getMultimediaObjectAdminLink($multimediaObject, $job->getMmId());

            $errorMessage = $this->subjectFails;
            $subject = ($this->platformName ? $this->platformName.': ' : '').$errorMessage;
            $template = $this->template;
            $parameters = array(
                                'subject' => $subject,
                                'job_status' => Job::$statusTexts[$job->getStatus()],
                                'job' => $job,
                                'commandLine' => $this->jobService->renderBat($job),
                                'sender_name' => $this->senderName,
                                'multimedia_object_admin_link' => $multimediaObjectAdminLink,
                                );
            $output = $this->senderService->sendNotification($job->getEmail(), $subject, $template, $parameters, true);

            return $output;
        }
    }

    private function getMultimediaObjectAdminLink($multimediaObject, $id = '')
    {
        if (null != $multimediaObject) {
            return $this->router->generate('pumukitnewadmin_mms_shortener', array('id' => $multimediaObject->getId()), true);
        }

        return 'No link found to Multimedia Object with id "'.$id.'".';
    }
}
