<?php

namespace Pumukit\StatsBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\StatsBundle\Document\ViewsLog;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Log
{
    private $dm;
    private $requestStack;
    private $tokenStorage;

    public function __construct(DocumentManager $documentManager, RequestStack $requestStack, TokenStorageInterface $tokenStorage)
    {
        $this->dm = $documentManager;
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
    }

    public function onMultimediaObjectViewed(ViewedEvent $event)
    {
        $req = $this->requestStack->getMasterRequest();

        $log = new ViewsLog(
            $req->getUri(),
            $req->getClientIp(),
            utf8_encode($req->headers->get('user-agent')),
            $req->headers->get('referer'),
            $event->getMultimediaObject()->getId(),
            $event->getMultimediaObject()->getSeries()->getId(),
            $event->getTrack() ? $event->getTrack()->getId() : null,
            $this->getUser()
        );

        $this->dm->persist($log);
        $this->dm->flush();
    }

    private function getUser()
    {
        if (null !== $token = $this->tokenStorage->getToken()) {
            if (($user = $token->getUser()) instanceof User) {
                return $user->getUsername();
            }
        }

        return null;
    }
}
