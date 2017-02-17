<?php

namespace Pumukit\NotificationBundle\Services;

use Symfony\Component\Translation\TranslatorInterface;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\JobService;
use Symfony\Component\Routing\RouterInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class JobNotificationService
{
    protected $senderService;
    protected $jobService;
    protected $platformName;
    protected $senderName;
    protected $environment;
    protected $translator;
    protected $router;

    public function __construct(SenderService $senderService, JobService $jobService, TranslatorInterface $translator, RouterInterface $router, $enable, $platformName, $senderName, $environment = 'dev')
    {
        $this->senderService = $senderService;
        $this->jobService = $jobService;
        $this->translator = $translator;
        $this->router = $router;
        $this->enable = $enable;
        $this->platformName = $platformName;
        $this->senderName = $senderName;
        $this->environment = $environment;
    }

    /**
     * On job success.
     *
     * @param JobEvent $event
     */
    public function onJobSuccess(JobEvent $event)
    {
        return $this->sendJobNotification($event, false);
    }

    /**
     * On job error.
     *
     * @param JobEvent $event
     */
    public function onJobError(JobEvent $event)
    {
        return $this->sendJobNotification($event, true);
    }

    /**
     * Send job notification according if the job
     * was succeeded or not.
     *
     * @param JobEvent $event
     * @param bool     $error
     *
     * @return bool
     */
    protected function sendJobNotification(JobEvent $event, $error = false)
    {
        if ($this->enable) {
            $job = $event->getJob();
            if (!$job) {
                return;
            }

            $multimediaObject = $event->getMultimediaObject();
            if (!($emailsTo = $this->getEmails($job, $multimediaObject))) {
                return;
            }

            $subject = $this->getSubjectEmail($job, $error);
            $parameters = $this->getParametersEmail($job, $multimediaObject, $subject);

            $output = $this->senderService->sendNotification($emailsTo, $subject, SenderService::TEMPLATE_JOB, $parameters, $error);

            return $output;
        }
    }

    /**
     * Get subject email.
     *
     * @param Job  $job
     * @param bool $error
     *
     * @return string
     */
    protected function getSubjectEmail(Job $job, $error = false)
    {
        if ($error) {
            $message = $this->translator->trans("Job with id '".$job->getId()."' failed");
        } else {
            $message = $this->translator->trans("Job with id '".$job->getId()."' successfully finished");
        }
        $subject = ($this->platformName ? $this->platformName.': ' : '').$message;

        return $subject;
    }

    /**
     * Get parameters email.
     *
     * @param Job              $job
     * @param MultimediaObject $multimediaObject
     * @param string           $subject
     *
     * @return array
     */
    protected function getParametersEmail(Job $job, MultimediaObject $multimediaObject, $subject)
    {
        $multimediaObjectAdminLink = $this->getMultimediaObjectAdminLink($multimediaObject, $job->getMmId());

        return array(
            'subject' => $subject,
            'job_status' => Job::$statusTexts[$job->getStatus()],
            'job' => $job,
            'commandLine' => $this->jobService->renderBat($job),
            'sender_name' => $this->senderName,
            'multimedia_object_admin_link' => $multimediaObjectAdminLink,
        );
    }

    /**
     * Get emails.
     *
     * @param Job              $job
     * @param MultimediaObject $multimediaObject
     *
     * @return string|array
     */
    protected function getEmails(Job $job, MultimediaObject $multimediaObject)
    {
        return $job->getEmail();
    }

    private function getMultimediaObjectAdminLink($multimediaObject, $id = '')
    {
        if (null != $multimediaObject) {
            return $this->router->generate('pumukitnewadmin_mms_shortener', array('id' => $multimediaObject->getId()), true);
        }

        return 'No link found to Multimedia Object with id "'.$id.'".';
    }
}
