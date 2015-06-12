<?php

namespace Pumukit\NotificationBundle\Services;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Process\ProcessBuilder;
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

    public function __construct($mailer, EngineInterface $templating, JobService $jobService, $enable, $platformName, $senderEmail, $senderName, $notificateErrorsToSender, $environment="dev")
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

            $successMessage = "Job '".$job->getId()."' successfully finished";
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

            $errorMessage = "Job '".$job->getId()."' failed";
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

            // TODO: spool type memory defined in config.yml. Needs to be executed the swiftmailer:spool:send command?
            $console = __DIR__ . '/../../../../app/console';
            $pb = new ProcessBuilder();
            $pb
              ->add('php')
              ->add($console)
              ->add(sprintf('--env=%s', $this->environment))
              ;

            $pb->add('swiftmailer:spool:send');
            $process = $pb->getProcess();            
            $command = $process->getCommandLine();

            shell_exec("nohup $command 1> /dev/null 2> /dev/null & echo $!");
        }
    }
}