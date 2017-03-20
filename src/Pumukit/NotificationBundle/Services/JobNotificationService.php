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
    protected $environment;
    protected $translator;
    protected $router;
    protected $template;
    protected $subjectSuccess;
    protected $subjectFails;
    protected $subjectSuccessTrans;
    protected $subjectFailsTrans;

    public function __construct(SenderService $senderService, JobService $jobService, TranslatorInterface $translator, RouterInterface $router, $enable, $environment, $template, $subjectSuccess, $subjectFails, $subjectSuccessTrans, $subjectFailsTrans)
    {
        $this->senderService = $senderService;
        $this->jobService = $jobService;
        $this->translator = $translator;
        $this->router = $router;
        $this->enable = $enable;
        $this->environment = $environment;
        $this->template = $template;
        $this->subjectSuccess = $subjectSuccess;
        $this->subjectFails = $subjectFails;
        $this->subjectSuccessTrans = $subjectSuccessTrans;
        $this->subjectFailsTrans = $subjectFailsTrans;
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
            $subjectInParameters = $this->getSubjectEmailInParameters($job, $error);
            $parameters = $this->getParametersEmail($job, $multimediaObject, $subjectInParameters);

            $output = $this->senderService->sendNotification($emailsTo, $subject, $this->template, $parameters, $error, true);

            return $output;
        }
    }

    /**
     * Get message.
     *
     * @param Job  $job
     * @param bool $error
     *
     * @return string
     */
    protected function getMessage(Job $job, $error = false)
    {
        if ($error) {
            return $this->subjectFails;
        }

        return $this->subjectSuccess;
    }

    /**
     * Get subject email in parameters.
     *
     * @param Job  $job
     * @param bool $error
     *
     * @return string
     */
    protected function getSubjectEmailInParameters(Job $job, $error = false)
    {
        $message = $this->getMessage($job, $error);
        $subject = ($this->senderService->getPlatformName() ? $this->senderService->getPlatformName().': ' : '').$message;

        return $subject;
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
        if (!$this->senderService->isMultiLangEnabled()) {
            return $this->getSubjectEmailInParameters($job, $error);
        }

        if ($error) {
            $subjectTrans = $this->subjectFailsTrans;
        } else {
            $subjectTrans = $this->subjectSuccessTrans;
        }
        $message = '';
        foreach ($subjectTrans as $translation) {
            if (isset($translation['subject'])) {
                $slash = $message ? ' / ' : '';
                $message = $message.$slash.$translation['subject'];
            }
        }

        $subjectEmail = ($this->senderService->getPlatformName() ? $this->senderService->getPlatformName().': ' : '').$message;

        return $subjectEmail;
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
            'sender_name' => $this->senderService->getSenderName(),
            'platform_name' => $this->senderService->getPlatformName(),
            'multimedia_object_admin_link' => $multimediaObjectAdminLink,
            'multimedia_object' => $multimediaObject,
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
