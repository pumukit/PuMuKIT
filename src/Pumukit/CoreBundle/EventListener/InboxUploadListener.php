<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\EventListener;

use MongoDB\BSON\ObjectId;
use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Event\UploadFileEvent;
use Symfony\Component\Process\Process;

class InboxUploadListener
{
    private $inboxPath;
    private $kernelProjectDir;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, string $inboxPath, string $kernelProjectDir)
    {
        $this->inboxPath = $inboxPath;
        $this->kernelProjectDir = $kernelProjectDir;
        $this->logger = $logger;
    }

    public function autoImport(UploadFileEvent $event): void
    {
        $filePath = $this->inboxPath.'/'.$event->getFileName();

        try {
            $objectId = new ObjectId($event->getSeries());
        } catch (\Exception $e) {
            $filePath = $this->inboxPath.'/'.$event->getSeries().'/'.$event->getFileName();
        }

        $command = [
            'php',
            $this->kernelProjectDir.'/bin/console',
            'pumukit:import:inbox',
            $filePath,
            '--user='.$event->getUser()->getUsername(),
            '--series='.$event->getSeries(),
            '--profile='.$event->getProfile(),
        ];

        $process = new Process($command);
        $command = $process->getCommandLine();

        $this->logger->warning($process->getCommandLine());

        shell_exec("nohup {$command} 1> /dev/null 2> /dev/null & echo $!");
    }
}
