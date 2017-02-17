<?php

namespace Pumukit\NotificationBundle\Services;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SenderService
{
    const TEMPLATE_JOB = 'PumukitNotificationBundle:Email:job.html.twig';
    const TEMPLATE_NOTIFICATION = 'PumukitNotificationBundle:Email:notification.html.twig';
    const TEMPLATE_ERROR = 'PumukitNotificationBundle:Email:error.html.twig';

    private $mailer;
    private $templating;
    private $jobService;
    private $senderEmail;
    private $senderName;
    private $adminEmail;
    private $notificateErrorsToAdmin;
    private $platformName;
    private $environment;
    private $translator;
    private $subject = "Can't send email to this address.";
    private $template = self::TEMPLATE_ERROR;

    public function __construct(
        $mailer,
        EngineInterface $templating,
        TranslatorInterface $translator,
        $enable,
        $senderEmail,
        $senderName,
        $adminEmail,
        $notificateErrorsToAdmin,
        $platformName,
        $environment = 'dev'
    ) {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->translator = $translator;
        $this->enable = $enable;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
        $this->adminEmail = $adminEmail;
        $this->notificateErrorsToAdmin = $notificateErrorsToAdmin;
        $this->platformName = $platformName;
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
     * Get Admin email.
     *
     * @return string|array
     */
    public function getAdminEmail()
    {
        return $this->adminEmail;
    }

    /**
     * Do notificate errors to admin.
     *
     * @return bool
     */
    public function doNotificateErrorsToAdmin()
    {
        return $this->notificateErrorsToAdmin;
    }

    /**
     * Get platform name.
     *
     * @return string
     */
    public function getPlatformName()
    {
        return $this->platformName;
    }

    /**
     * Send notification.
     *
     * @param $emailTo
     * @param $subject
     * @param $template
     * @param array $parameters
     * @param bool  $error
     *
     * @return bool
     */
    public function sendNotification($emailTo, $subject, $template, array $parameters = array(), $error = true)
    {
        $filterEmail = $this->filterEmail($emailTo);

        if ($this->enable && ($filterEmail['verified'] || $filterEmail['error'])) {
            if ($filterEmail['verified']) {

                $sent = $this->sendEmailTemplate(
                    $filterEmail['verified'],
                    $subject,
                    $template,
                    $parameters,
                    $error
                );
            }

            if ($filterEmail['error']) {
                $parameters['body'] = $filterEmail['error'];

                $this->sendEmailTemplate(
                    $this->senderEmail,
                    $this->subject,
                    $this->template,
                    $parameters,
                    $error
                );
            }

            return $sent;
        }

        return false;
    }

    /**
     * Checks if string|array email are valid.
     *
     * @param string|array $emailTo
     *
     * @return bool
     */
    private function filterEmail($emailTo)
    {
        $verifiedEmails = array();
        $errorEmails = array();
        if (is_array($emailTo)) {
            foreach ($emailTo as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL) != false) {
                    $verifiedEmails[] = $email;
                } else {
                    $errorEmails[] = $email;
                }
            }
        } else {
            if (filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
                $verifiedEmails[] = $emailTo;
            } else {
                $errorEmails[] = $emailTo;
            }
        }

        $filterEmail = array(
            'verified' => $verifiedEmails,
            'error' => $errorEmails,
        );

        return $filterEmail;
    }

    /**
     * Create the email and send.
     *
     * @param $emailTo
     * @param $subject
     * @param $template
     * @param $parameters
     * @param $error
     *
     * @return mixed
     */
    private function sendEmailTemplate($emailTo, $subject, $template, $parameters, $error)
    {
        $message = \Swift_Message::newInstance();
        if ($error && $this->notificateErrorsToAdmin) {
            $message->addBcc($this->adminEmail);
        }

        /* Send to verified emails */
        $message
            ->setSubject($subject)
            ->setSender($this->senderEmail, $this->senderName)
            ->setFrom($this->senderEmail, $this->senderName)
            ->addReplyTo($this->senderEmail, $this->senderName)
            ->setTo($emailTo)
            ->setBody($this->templating->render($template, $parameters), 'text/html');

        return $this->mailer->send($message);
    }
}
