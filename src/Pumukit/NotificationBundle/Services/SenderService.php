<?php

declare(strict_types=1);

namespace Pumukit\NotificationBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Repository\PersonRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class SenderService
{
    public const TEMPLATE_JOB = '@PumukitNotification/Email/job.html.twig';
    public const TEMPLATE_NOTIFICATION = '@PumukitNotification/Email/notification.html.twig';
    public const TEMPLATE_ERROR = '@PumukitNotification/Email/error.html.twig';

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
    private $translator;
    private $subject = "Can't send email to this address.";
    private $template = self::TEMPLATE_ERROR;
    private $logger;
    /** @var PersonRepository */
    private $personRepo;
    private $enable;
    private $session;

    public function __construct(
        \Swift_Mailer $mailer,
        Environment $templating,
        TranslatorInterface $translator,
        DocumentManager $documentManager,
        LoggerInterface $logger,
        SessionInterface $session,
        $enable,
        $senderEmail,
        $senderName,
        $enableMultiLang,
        $locales,
        $subjectSuccessTrans,
        $subjectFailsTrans,
        $adminEmail,
        $notificateErrorsToAdmin,
        $platformName
    ) {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->translator = $translator;
        $this->logger = $logger;
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
        $this->personRepo = $documentManager->getRepository(Person::class);
        $this->session = $session;
    }

    public function isEnabled()
    {
        return $this->enable;
    }

    public function getSenderEmail(): string
    {
        return $this->senderEmail;
    }

    public function getSenderName(): string
    {
        return $this->senderName;
    }

    public function isMultiLangEnabled(): bool
    {
        return $this->enableMultiLang;
    }

    public function getAdminEmail()
    {
        return $this->adminEmail;
    }

    public function doNotificationErrorsToAdmin(): bool
    {
        return $this->notificateErrorsToAdmin;
    }

    public function getPlatformName(): string
    {
        return $this->platformName;
    }

    public function getSubjectSuccessTrans(): array
    {
        return $this->subjectSuccessTrans;
    }

    public function getSubjectFailsTrans(): array
    {
        return $this->subjectFailsTrans;
    }

    public function getLocales(): array
    {
        return $this->locales;
    }

    public function sendEmails($emailsTo, string $subjectString, string $templateString, array $parameters = []): void
    {
        if (!is_array($emailsTo)) {
            $emailsTo = [$emailsTo];
        }

        if (!$this->enable) {
            $this->logger->info(self::class.'['.__FUNCTION__.'] The email sender service is disabled. Not sending emails to "'.implode(', ', $emailsTo).'"');

            return;
        }

        foreach ($emailsTo as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { //No need for a separate filtering function when "filtering" is a single function call.
                $this->logger->warning(self::class.'['.__FUNCTION__.'] The email "'.$email.'" appears as invalid. Message will not be sent.');

                continue;
            }

            $twig = new Environment(new ArrayLoader());
            $template = $twig->createTemplate($templateString);
            $body = $template->render($parameters);
            $subjectTemplate = $twig->createTemplate($subjectString);
            $subject = $subjectTemplate->render($parameters);
            $message = new \Swift_Message();
            $message
                ->setSubject($subject)
                ->setSender($this->senderEmail, $this->senderName)
                ->setFrom($this->senderEmail, $this->senderName)
                ->addReplyTo($this->senderEmail, $this->senderName)
                ->setTo($email)
                ->setBody($body, 'text/html')
                ->addPart($body, 'text/plain')
            ;

            $this->mailer->send($message);
        }
    }

    public function sendNotification($emailTo, string $subject, string $template, array $parameters = [], bool $error = true, bool $transConfigSubject = false): bool
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

    public function getMessageToSend(\Swift_Message $message, string $email, string $subject, string $template, array $parameters, bool $error, bool $transConfigSubject)
    {
        $body = $this->getBodyInMultipleLanguages($template, $parameters, $error, $transConfigSubject);

        $message
            ->setSubject($subject)
            ->setSender($this->senderEmail, $this->senderName)
            ->setFrom($this->senderEmail, $this->senderName)
            ->addReplyTo($this->senderEmail, $this->senderName)
            ->setTo($email)
            ->setBody($body, 'text/html')
            ->addPart($body, 'text/plain')
        ;

        return $message;
    }

    public function getBodyInMultipleLanguages(string $template, array $parameters, bool $error, bool $transConfigSubject): string
    {
        if (!$this->enableMultiLang) {
            return $this->templating->render($template, $parameters);
        }

        $sessionLocale = $this->session->get('_locale');
        $body = '';
        foreach ($this->locales as $locale) {
            $this->session->set('_locale', $locale);
            $parameters = $this->transConfigurationSubject($parameters, $locale, $error, $transConfigSubject);
            $parameters['locale'] = $locale;
            $bodyLocale = $this->templating->render($template, $parameters);
            $body .= $bodyLocale;
        }
        $this->session->set('_locale', $sessionLocale);

        return $body;
    }

    public function getSubjectSuccessTransWithLocale(string $locale = 'en'): string
    {
        return $this->getSubjectTransWithLocale($this->subjectSuccessTrans, $locale);
    }

    public function getSubjectFailsTransWithLocale(string $locale = 'en'): string
    {
        return $this->getSubjectTransWithLocale($this->subjectFailsTrans, $locale);
    }

    public function getSubjectTransWithLocale(array $subjectArray = [], string $locale = 'en'): ?string
    {
        foreach ($subjectArray as $translation) {
            if (isset($translation['locale'], $translation['subject']) && ($locale === $translation['locale'])) {
                return $translation['subject'];
            }
        }

        return null;
    }

    private function filterEmail($emailTo): array
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
        } elseif (filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
            $verifiedEmails[] = $emailTo;
        } else {
            $errorEmails[] = $emailTo;
        }

        return [
            'verified' => $verifiedEmails,
            'error' => $errorEmails,
        ];
    }

    private function sendEmailTemplate($emailTo, string $subject, string $template, array $parameters, bool $error, bool $transConfigSubject)
    {
        $message = new \Swift_Message();
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
        }
        $parameters['person_name'] = $this->getPersonNameFromEmail($emailTo);
        $message = $this->getMessageToSend($message, $emailTo, $subject, $template, $parameters, $error, $transConfigSubject);

        return $this->mailer->send($message);
    }

    private function transConfigurationSubject(array $parameters, string $locale, bool $error, bool $transConfigSubject): array
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

    private function getPersonNameFromEmail(string $email): string
    {
        $personName = $email;
        $person = $this->personRepo->findOneBy(['email' => $email]);
        if ($person) {
            $personName = $person->getHName();
        }

        return $personName;
    }
}
