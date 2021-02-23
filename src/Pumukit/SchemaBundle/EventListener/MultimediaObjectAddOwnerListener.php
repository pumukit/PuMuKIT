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
        $coOwner = $event->getCoOwner();

        if($user->getUsername() !== $coOwner->getUsername()) {
            $this->sendNotificationEmail($multimediaObject, $user, $coOwner);
        }
    }

    private function sendNotificationEmail(MultimediaObject $multimediaObject, UserInterface $user, UserInterface $coOwner): void
    {
        $subject = implode(' ', [
            $this->getPredefinedSubject(),
            $multimediaObject->getTitle(),
        ]);

        $this->senderService->sendNotification(
            $coOwner->getEmail(),
            $subject,
            $this->getPredefinedEmailTemplate(),
            $this->generateParametersForEmail($multimediaObject, $user, $coOwner, $subject),
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

    private function generateParametersForEmail(MultimediaObject $multimediaObject, UserInterface $user, UserInterface $coOwner, string $subject): array
    {
        return [
            'platform_name' => $this->senderService->getPlatformName(),
            'subject' => $subject,
            'user' => $user,
            'coOwner' => $coOwner,
            'multimediaObject' => $multimediaObject,
            'sender_name' => $this->senderService->getSenderName(),
        ];
    }
}
