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

    public function __construct($mailer, EngineInterface $templating, TranslatorInterface $translator, $enable, $senderEmail, $senderName, $notificateErrorsToSender, $environment = 'dev')
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
     * IsEnabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enable;
    }

    /**
     * Get Sender email.
     *
     * @return string
     */
    public function getSenderEmail()
    {
        return $this->senderEmail;
    }

    /**
     * Get Sender name.
     *
     * @return string
     */
    public function getSenderName()
    {
        return $this->senderName;
    }

    /**
     * Do notificate errors to sender.
     *
     * @return bool
     */
    public function doNotificateErrorsToSender()
    {
        return $this->notificateErrorsToSender;
    }

    /**
     *
     * Send notification
     *
     * @param $emailTo
     * @param $subject
     * @param $template
     * @param array $parameters
     * @param bool $error
     * @return bool
     */
    public function sendNotification($emailTo, $subject, $template, $parameters = array(), $error = true)
    {
        $filterEmail = $this->filterEmail($emailTo);

        if ($this->enable && $filterEmail) {
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

    /**
     * Checks if string|array email are valid
     *
     * @param string|array $emailTo
     * @return bool
     */
    private function filterEmail($emailTo)
    {
        $filterEmail = false;
        if(is_array($emailTo)) {
            foreach($emailTo as $email) {
                if( filter_var($email, FILTER_VALIDATE_EMAIL) != false ) {
                    $filterEmail = true;
                } else {
                    $filterEmail = false;
                }
            }
        } else {
            if(filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
                $filterEmail = true;
            }
        }

        return $filterEmail;
    }
}
