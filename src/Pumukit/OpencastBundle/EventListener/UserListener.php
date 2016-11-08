<?php

namespace Pumukit\OpencastBundle\EventListener;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Pumukit\OpencastBundle\Services\ClientService;
use Pumukit\SchemaBundle\Event\UserEvent;

class UserListener
{
    private $clientService;
    private $logger;
    private $manageOpencastUsers;

    public function __construct(ClientService $clientService, LoggerInterface $logger, $manageOpencastUsers = false)
    {
        $this->clientService = $clientService;
        $this->logger = $logger;
        $this->manageOpencastUsers = $manageOpencastUsers;
    }

    public function onUserCreate(UserEvent $event)
    {
        if ($this->manageOpencastUsers) {
            try {
                $user = $event->getUser();
                $output = $this->clientService->createUser($user);
                if (!$output) {
                    throw new \Exception('Error on creating an User on the Opencast Server');
                }
                $this->logger->addDebug('Created User "'.$user->getUsername().'" on the Opencast Server');
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), $e->getTrace());
            }
        }
    }

    public function onUserUpdate(UserEvent $event)
    {
        if ($this->manageOpencastUsers) {
            try {
                $user = $event->getUser();
                $output = $this->clientService->updateUser($user);
                if (!$output) {
                    throw new \Exception('Error on updating an User on the Opencast Server');
                }
                $this->logger->addDebug('Updated User "'.$user->getUsername().'" on the Opencast Server');
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), $e->getTrace());
            }
        }
    }

    public function onUserDelete(UserEvent $event)
    {
        if ($this->manageOpencastUsers) {
            try {
                $user = $event->getUser();
                $output = $this->clientService->deleteUser($user);
                if (!$output) {
                    throw new \Exception('Error on deleting an User on the Opencast Server');
                }
                $this->logger->addDebug('Deleted User "'.$user->getUsername().'" on the Opencast Server');
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), $e->getTrace());
            }
        }
    }
}
