<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\EventListener;

use FOS\UserBundle\Model\UserInterface;
use Pumukit\NotificationBundle\Services\SenderService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Event\MultimediaObjectAddOwnerEvent;

class MultimediaObjectAddOwnerListener
{
    private $senderService;
    private $addedOwnerEmailSubject;
    private $addedOwnerEmailTemplate;

    public function __construct(
        SenderService $senderService,
        string $addedOwnerEmailSubject,
        string $addedOwnerEmailTemplate
    ) {
        $this->senderService = $senderService;
        $this->addedOwnerEmailSubject = $addedOwnerEmailSubject;
        $this->addedOwnerEmailTemplate = $addedOwnerEmailTemplate;
    }

    public function add(MultimediaObjectAddOwnerEvent $event): void
    {
        $multimediaObject = $event->getMultimediaObject();
        $user = $event->getUser();

        $this->sendNotificationEmail($multimediaObject, $user);
    }

    private function sendNotificationEmail(MultimediaObject $multimediaObject, UserInterface $user): void
    {
        $subject = implode(' ', [
            $this->getPredefinedSubject(),
            $multimediaObject->getTitle(),
        ]);

        $this->senderService->sendNotification(
            $user->getEmail(),
            $subject,
            $this->getPredefinedEmailTemplate(),
            $this->generateParametersForEmail($multimediaObject, $user, $subject),
            false
        );
    }

    private function getPredefinedSubject(): string
    {
        return $this->addedOwnerEmailSubject;
    }

    private function getPredefinedEmailTemplate(): string
    {
        return $this->addedOwnerEmailTemplate;
    }

    private function generateParametersForEmail(MultimediaObject $multimediaObject, UserInterface $user, string $subject): array
    {
        return [
            'platform_name' => $this->senderService->getPlatformName(),
            'subject' => $subject,
            'user' => $user,
            'multimediaObject' => $multimediaObject,
            'sender_name' => $this->senderService->getSenderName(),
        ];
    }
}
