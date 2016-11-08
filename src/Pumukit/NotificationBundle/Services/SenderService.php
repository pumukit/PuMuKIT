<?php

namespace Pumukit\NotificationBundle\Services;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SenderService
{
    private $mailer;
    private $templating;
    private $jobService;
    private $senderEmail;
    private $senderName;
    private $notificateErrorsToSender;
    private $environment;
    private $translator;

    public function __construct($mailer, EngineInterface $templating, TranslatorInterface $translator, $enable, $senderEmail, $senderName, $notificateErrorsToSender, $environment="dev")
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->translator = $translator;
        $this->enable = $enable;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
        $this->notificateErrorsToSender = $notificateErrorsToSender;
        $this->environment = $environment;
    }

    /**
     * IsEnabled
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enable;
    }

    /**
     * Get Sender email
     *
     * @return string
     */
    public function getSenderEmail()
    {
        return $this->senderEmail;
    }

    /**
     * Get Sender name
     *
     * @return string
     */
    public function getSenderName()
    {
        return $this->senderName;
    }

    /**
     * Do notificate errors to sender
     *
     * @return boolean
     */
    public function doNotificateErrorsToSender()
    {
        return $this->notificateErrorsToSender;
    }


    /**
     * Send notification
     *
     * @param string $emailTo
     * @param string $subject
     * @param string $template
     * @param array $parameters
     * @param boolean $error
     */
    public function sendNotification($emailTo, $subject = 'Pumukit2 Notification', $template, $parameters=array(), $error=true)
    {
        if ($this->enable && filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
            $message = \Swift_Message::newInstance();
            if ($error && $this->notificateErrorsToSender) {
                $message->addBcc($this->senderEmail);
            }
            $message
              ->setSubject($subject)
              ->setSender($this->senderEmail, $this->senderName)
              ->setFrom($this->senderEmail, $this->senderName)
              ->addReplyTo($this->senderEmail, $this->senderName)
              ->setTo($emailTo)
              ->setBody($this->templating->render($template, $parameters), 'text/html');
            $sent = $this->mailer->send($message);
            return $sent;
        }
        return false;
    }
}
