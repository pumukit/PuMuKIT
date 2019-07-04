<?php

namespace Pumukit\NotificationBundle\Services;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Person;

class SenderService
{
    const TEMPLATE_JOB = 'PumukitNotificationBundle:Email:job.html.twig';
    const TEMPLATE_NOTIFICATION = 'PumukitNotificationBundle:Email:notification.html.twig';
    const TEMPLATE_ERROR = 'PumukitNotificationBundle:Email:error.html.twig';

    private $mailer;
    private $templating;
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
    private $dm;
    private $personRepo;
    private $enable;

    public function __construct(
        $mailer,
        EngineInterface $templating,
        TranslatorInterface $translator,
        DocumentManager $documentManager,
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
        $this->dm = $documentManager;
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
        $this->personRepo = $this->dm->getRepository(Person::class);
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
     * Get Subject Success Trans.
     *
     * @return array
     */
    public function getSubjectSuccessTrans()
    {
        return $this->subjectSuccessTrans;
    }

    /**
     * Get Subject Fails Trans.
     *
     * @return array
     */
    public function getSubjectFailsTrans()
    {
        return $this->subjectFailsTrans;
    }

    /**
     * Get locales.
     *
     * @return array
     */
    public function getLocales()
    {
        return $this->locales;
    }

    /**
     * Send notification.
     *
     * @param $emailTo
     * @param $subject
     * @param $template
     * @param array $parameters
     * @param bool  $error
     * @param bool  $transConfigSubject
     *
     * @return bool
     */
    public function sendNotification($emailTo, $subject, $template, array $parameters = [], $error = true, $transConfigSubject = false)
    {
        $filterEmail = $this->filterEmail($emailTo);
        $sent = false;
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

                $sent = $this->sendEmailTemplate(
                    $this->senderEmail,
                    $this->subject,
                    $this->template,
                    $parameters,
                    $error,
                    $transConfigSubject
                );
            }
        }

        return $sent;
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
        $verifiedEmails = [];
        $errorEmails = [];
        if (is_array($emailTo)) {
            foreach ($emailTo as $email) {
                if (false !== filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
        $filterEmail = [
            'verified' => $verifiedEmails,
            'error' => $errorEmails,
        ];

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
            if (is_array($this->adminEmail)) {
                foreach ($this->adminEmail as $admin) {
                    $message->addBcc($admin);
                }
            } else {
                $message->addBcc($this->adminEmail);
            }
        }

        if (is_array($emailTo)) {
            $aux = 0;
            foreach ($emailTo as $email) {
                $parameters['person_name'] = $this->getPersonNameFromEmail($email);
                $message = $this->getMessageToSend($message, $email, $subject, $template, $parameters, $error, $transConfigSubject);
                $aux += $this->mailer->send($message);
            }

            return $aux;
        } else {
            $parameters['person_name'] = $this->getPersonNameFromEmail($emailTo);
            $message = $this->getMessageToSend($message, $emailTo, $subject, $template, $parameters, $error, $transConfigSubject);
        }

        return $this->mailer->send($message);
    }

    /**
     * Get message to send.
     *
     * @param $message
     * @param $email
     * @param $subject
     * @param $template
     * @param $parameters
     * @param $error
     * @param $transConfigSubject
     *
     * @return mixed
     */
    public function getMessageToSend($message, $email, $subject, $template, $parameters, $error, $transConfigSubject)
    {
        $body = $this->getBodyInMultipleLanguages($template, $parameters, $error, $transConfigSubject);

        /* Send to verified emails */
        $message
            ->setSubject($subject)
            ->setSender($this->senderEmail, $this->senderName)
            ->setFrom($this->senderEmail, $this->senderName)
            ->addReplyTo($this->senderEmail, $this->senderName)
            ->setTo($email)
            ->setBody($body, 'text/html');

        return $message;
    }

    /**
     * Get body in multiple languages.
     *
     * @param string $template
     * @param array  $parameters
     * @param bool   $error
     * @param bool   $transConfigSubject
     *
     * @return string
     */
    public function getBodyInMultipleLanguages($template, $parameters, $error, $transConfigSubject)
    {
        if (!$this->enableMultiLang) {
            return $this->templating->render($template, $parameters);
        }

        $sessionLocale = $this->translator->getLocale();
        $body = '';
        foreach ($this->locales as $locale) {
            $this->translator->setLocale($locale);
            $parameters = $this->transConfigurationSubject($parameters, $locale, $error, $transConfigSubject);
            $parameters['locale'] = $locale;
            $bodyLocale = $this->templating->render($template, $parameters);
            $body = $body.$bodyLocale;
        }
        $this->translator->setLocale($sessionLocale);

        return $body;
    }

    private function transConfigurationSubject($parameters, $locale, $error, $transConfigSubject)
    {
        if ($transConfigSubject) {
            if ($error) {
                $subject = $this->getSubjectFailsTransWithLocale($locale);
            } else {
                $subject = $this->getSubjectSuccessTransWithLocale($locale);
            }
            $parameters['subject'] = ($this->getPlatformName() ? $this->getPlatformName().': ' : '').$subject;
        }

        return $parameters;
    }

    /**
     * Get Subject Success Trans With Locale.
     *
     * @return string
     */
    public function getSubjectSuccessTransWithLocale($locale = 'en')
    {
        return $this->getSubjectTransWithLocale($this->subjectSuccessTrans, $locale);
    }

    /**
     * Get Subject Fails Trans With Locale.
     *
     * @return string
     */
    public function getSubjectFailsTransWithLocale($locale = 'en')
    {
        return $this->getSubjectTransWithLocale($this->subjectFailsTrans, $locale);
    }

    /**
     * Get Subject Trans With Locale.
     *
     * @return string
     */
    public function getSubjectTransWithLocale(array $subjectArray = [], $locale = 'en')
    {
        foreach ($subjectArray as $translation) {
            if (isset($translation['locale']) && ($locale == $translation['locale']) && isset($translation['subject'])) {
                return $translation['subject'];
            }
        }

        return null;
    }

    private function getPersonNameFromEmail($email)
    {
        $personName = $email;
        $person = $this->personRepo->findOneByEmail($email);
        if ($person) {
            $personName = $person->getHName();
        }

        return $personName;
    }
}
