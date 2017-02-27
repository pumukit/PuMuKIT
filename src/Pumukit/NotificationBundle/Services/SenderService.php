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
    private $enableMultiLang;
    private $locales;
    private $subjectSuccessTrans;
    private $subjectFailsTrans;
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
        $enableMultiLang,
        $locales,
        $subjectSuccessTrans,
        $subjectFailsTrans,
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
        $this->enableMultiLang = $enableMultiLang;
        $this->locales = $locales;
        $this->subjectSuccessTrans = $subjectSuccessTrans;
        $this->subjectFailsTrans = $subjectFailsTrans;
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
     * IsMultiLangEnabled.
     *
     * @return bool
     */
    public function isMultiLangEnabled()
    {
        return $this->enableMultiLang;
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
     * Get Subject Success Trans
     *
     * @return array
     */
    public function getSubjectSuccessTrans()
    {
        return $this->subjectSuccessTrans;
    }

    /**
     * Get Subject Fails Trans
     *
     * @return array
     */
    public function getSubjectFailsTrans()
    {
        return $this->subjectFailsTrans;
    }

    /**
     * Send notification.
     *
     * @param $emailTo
     * @param $subject
     * @param $template
     * @param array $parameters
     * @param bool  $error
     * @param bool $transConfigSubject
     *
     * @return bool
     */
    public function sendNotification($emailTo, $subject, $template, array $parameters = array(), $error = true, $transConfigSubject = false)
    {
        $filterEmail = $this->filterEmail($emailTo);

        if ($this->enable && ($filterEmail['verified'] || $filterEmail['error'])) {
            if ($filterEmail['verified']) {
                $sent = $this->sendEmailTemplate(
                    $filterEmail['verified'],
                    $subject,
                    $template,
                    $parameters,
                    $error,
                    $transConfigSubject
                );
            }

            if ($filterEmail['error']) {
                $parameters['body'] = $filterEmail['error'];

                $this->sendEmailTemplate(
                    $this->senderEmail,
                    $this->subject,
                    $this->template,
                    $parameters,
                    $error,
                    $transConfigSubject
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
     * @param $transConfigSubject
     *
     * @return mixed
     */
    private function sendEmailTemplate($emailTo, $subject, $template, $parameters, $error, $transConfigSubject)
    {
        $message = \Swift_Message::newInstance();
        if ($error && $this->notificateErrorsToAdmin) {
            $message->addBcc($this->adminEmail);
        }

        $body = $this->getBodyInMultipleLanguages($template, $parameters, $transConfigSubject, $error);

        /* Send to verified emails */
        $message
            ->setSubject($subject)
            ->setSender($this->senderEmail, $this->senderName)
            ->setFrom($this->senderEmail, $this->senderName)
            ->addReplyTo($this->senderEmail, $this->senderName)
            ->setTo($emailTo)
            ->setBody($body, 'text/html');

        return $this->mailer->send($message);
    }

    /**
     * Get body in multiple languages
     *
     * @param string $template
     * @param array  $parameters
     * @param bool   $transConfigSubject
     *
     * @return string
     */
    public function getBodyInMultipleLanguages($template, $parameters, $transConfigSubject, $error)
    {
        if (!$this->enableMultiLang) {
            return $this->templating->render($template, $parameters);
        }

        $sessionLocale = $this->translator->getLocale();
        $body = '';
        foreach ($this->locales as $locale) {
            $this->translator->setLocale($locale);
            $parameters = $this->transConfigurationSubject($parameters, $transConfigSubject, $locale, $error);
            $bodyLocale = $this->templating->render($template, $parameters);
            $body = $body . $bodyLocale;
        }
        $this->translator->setLocale($sessionLocale);

        return $body;
    }

    private function transConfigurationSubject($parameters, $transConfigSubject, $locale, $error)
    {
        if ($transConfigSubject) {
            if ($error) {
                $subject = $this->getSubjectSuccessTransWithLocale($locale);
            } else {
                $subject = $this->getSubjectFailsTransWithLocale($locale);
            }
            $parameters['subject'] = ($this->getPlatformName() ? $this->getPlatformName().': ' : '').$subject;
        }

        return $parameters;
    }

    /**
     * Get Subject Success Trans With Locale
     *
     * @return string
     */
    public function getSubjectSuccessTransWithLocale($locale = 'en')
    {
        return $this->getSubjectTransWithLocale($this->subjectSuccessTrans, $locale);
    }

    /**
     * Get Subject Fails Trans With Locale
     *
     * @return string
     */
    public function getSubjectFailsTransWithLocale($locale = 'en')
    {
        return $this->getSubjectTransWithLocale($this->subjectFailsTrans, $locale);
    }

    /**
     * Get Subject Trans With Locale
     *
     * @return string
     */
    public function getSubjectTransWithLocale(array $subjectArray = array(), $locale = 'en')
    {
        foreach ($subjectArray as $translation) {
            if (isset($translation['locale']) && ($locale == $translation['locale']) && isset($translation['subject'])){
                return $translation['subject'];
            }
        }

        return null;
    }
}
