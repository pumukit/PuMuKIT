<?php

namespace Pumukit\NotificationBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class JobNotificationService
{
    const PERSONAL_SCOPE_ROLE_CODE = 'owner';
    protected $dm;
    protected $senderService;
    protected $jobService;
    protected $translator;
    protected $router;
    protected $enable;
    protected $environment;
    protected $template;
    protected $subjectSuccess;
    protected $subjectFails;
    protected $subjectSuccessTrans;
    protected $subjectFailsTrans;

    public function __construct(DocumentManager $documentManager, SenderService $senderService, JobService $jobService, TranslatorInterface $translator, RouterInterface $router, $enable, $environment, $template, $subjectSuccess, $subjectFails, $subjectSuccessTrans, $subjectFailsTrans)
    {
        $this->dm = $documentManager;
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
     * @return bool
     */
    public function onJobSuccess(JobEvent $event)
    {
        return $this->sendJobNotification($event, false);
    }

    /**
     * On job error.
     *
     * @return bool
     */
    public function onJobError(JobEvent $event)
    {
        return $this->sendJobNotification($event, true);
    }

    /**
     * Send job notification according if the job
     * was succeeded or not.
     *
     * @param bool $error
     *
     * @return bool
     */
    protected function sendJobNotification(JobEvent $event, $error = false)
    {
        if ($this->enable) {
            $job = $event->getJob();

            $track = $event->getTrack();
            if (!$error && ($track->isMaster() && !$track->containsTag('display'))) {
                return false;
            }

            $multimediaObject = $event->getMultimediaObject();
            if (!($emailsTo = $this->getEmails($job, $multimediaObject))) {
                return false;
            }

            $subject = $this->getSubjectEmail($job, $error);
            $subjectInParameters = $this->getSubjectEmailInParameters($job, $error);
            $parameters = $this->getParametersEmail($job, $multimediaObject, $subjectInParameters);

            if (!$emailsTo) {
                return false;
            }
            if (is_array($emailsTo)) {
                $output = false;
                foreach ($emailsTo as $email) {
                    $output = $this->senderService->sendNotification($email, $subject, $this->template, $parameters, $error, true);
                }
            } else {
                $output = $this->senderService->sendNotification($emailsTo, $subject, $this->template, $parameters, $error, true);
            }

            return $output;
        }

        return false;
    }

    /**
     * Get message.
     *
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
     * @param bool $error
     *
     * @return string
     */
    protected function getSubjectEmailInParameters(Job $job, $error = false)
    {
        $message = $this->getMessage($job, $error);

        return ($this->senderService->getPlatformName() ? $this->senderService->getPlatformName().': ' : '').$message;
    }

    /**
     * Get subject email.
     *
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

        return ($this->senderService->getPlatformName() ? $this->senderService->getPlatformName().': ' : '').$message;
    }

    /**
     * Get parameters email.
     *
     * @param string $subject
     *
     * @return array
     */
    protected function getParametersEmail(Job $job, MultimediaObject $multimediaObject, $subject)
    {
        $multimediaObjectAdminLink = $this->getMultimediaObjectAdminLink($multimediaObject, $job->getMmId());

        return [
            'subject' => $subject,
            'job_status' => Job::$statusTexts[$job->getStatus()],
            'job' => $job,
            'commandLine' => $this->jobService->renderBat($job),
            'sender_name' => $this->senderService->getSenderName(),
            'platform_name' => $this->senderService->getPlatformName(),
            'multimedia_object_admin_link' => $multimediaObjectAdminLink,
            'multimedia_object' => $multimediaObject,
        ];
    }

    /**
     * Get emails.
     *
     * @return array|string
     */
    protected function getEmails(Job $job, MultimediaObject $multimediaObject)
    {
        $emailsTo = [];

        if (Job::STATUS_FINISHED === $job->getStatus()) {
            $aPeople = $multimediaObject->getPeopleByRoleCod(self::PERSONAL_SCOPE_ROLE_CODE, true);
            foreach ($aPeople as $people) {
                $user = $this->dm->getRepository(User::class)->findOneBy(['email' => $people->getEmail()]);
                if ($user && ($user->hasRole(Permission::ROLE_SEND_NOTIFICATION_COMPLETE) || $user->hasRole('ROLE_SUPER_ADMIN'))) {
                    $emailsTo[] = $user->getEmail();
                }
            }
        } elseif (Job::STATUS_ERROR === $job->getStatus()) {
            $aPeople = $multimediaObject->getPeopleByRoleCod(self::PERSONAL_SCOPE_ROLE_CODE, true);

            foreach ($aPeople as $people) {
                $user = $this->dm->getRepository(User::class)->findOneBy(['email' => $people->getEmail()]);
                if ($user && ($user->hasRole(Permission::ROLE_SEND_NOTIFICATION_ERRORS) || $user->hasRole('ROLE_SUPER_ADMIN'))) {
                    $emailsTo[] = $user->getEmail();
                }
            }
        }

        return $emailsTo;
    }

    private function getMultimediaObjectAdminLink($multimediaObject, $id = '')
    {
        if (null !== $multimediaObject) {
            return $this->router->generate('pumukitnewadmin_mms_shortener', ['id' => $multimediaObject->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return 'No link found to Multimedia Object with id "'.$id.'".';
    }
}
