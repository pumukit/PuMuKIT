<?php

namespace Pumukit\NotificationBundle\Services;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\JobService;

class NotificationService
{
    private $mailer;
    private $templating;
    private $jobService;
    private $platformName;
    private $senderEmail;
    private $senderName;
    private $notificateErrorsToSender;
    private $environment;
    private $translator;

    public function __construct($mailer, EngineInterface $templating, JobService $jobService, $enable, $platformName, $senderEmail, $senderName, $notificateErrorsToSender, $environment="dev", TranslatorInterface $translator)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->jobService = $jobService;
        $this->enable = $enable;
        $this->platformName = $platformName;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
        $this->notificateErrorsToSender = $notificateErrorsToSender;
        $this->environment = $environment;
        $this->translator = $translator;
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
            $this->sendJobNotification($event->getJob(), $subject, false);
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
            $this->sendJobNotification($event->getJob(), $subject, true);
        }
    }

    /**
     * Send job notification
     *
     * @param Job $job
     * @param string $subject
     * @param boolean $error
     */
    private function sendJobNotification(Job $job, $subject='Pumukit job', $error=true)
    {
        if (eregi('^[\x20-\x2D\x2F-\x7E]+(\.[\x20-\x2D\x2F-\x7E]+)*@(([a-z0-9]([-a-z0-9]*[a-z0-9]+)?){1,63}\.)+[a-z0-9]{2,6}$', $job->getEmail())) {
            $message = \Swift_Message::newInstance();
            if ($error && $this->notificateErrorsToSender) {
                $message->addBcc($this->senderEmail);
            }
            $message
              ->setSubject($subject)
              ->setSender($this->senderEmail, $this->senderName)
              ->setFrom($this->senderEmail, $this->senderName)
              ->addReplyTo($this->senderEmail, $this->senderName)
              ->setTo($job->getEmail())
              ->setBody($this->templating->render('PumukitNotificationBundle:Email:job.html.twig', array('subject' => $subject, 'job_status' => Job::$statusTexts[$job->getStatus()],  'job' => $job, 'commandLine' => $this->jobService->renderBat($job), 'sender_name' => $this->senderName)), 'text/html');

            $sent = $this->mailer->send($message);
        }
    }
}