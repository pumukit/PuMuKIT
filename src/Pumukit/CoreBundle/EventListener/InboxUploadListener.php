<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\EventListener;

use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Event\InboxUploadEvent;
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

    public function autoImport(InboxUploadEvent $event): void
    {
        $filePath = $this->inboxPath.'/'.$event->getFileName();

        // TODO: DIGIREPO REMOVE
//        if (null !== $event->getFolder()) {
//            $urlUpload = $this->inboxPath.'/'.$event->getFolder().'/'.$event->getFileName();
//        }

        $command = [
            'php',
            $this->kernelProjectDir.'/bin/console',
            'pumukit:import:inbox',
            $filePath,
            '--user='.$event->getUser()->getUsername(),
            '--series='.$event->getSeries(),
        ];

        $process = new Process($command);
        $command = $process->getCommandLine();

        $this->logger->warning($process->getCommandLine());

        shell_exec("nohup {$command} 1> /dev/null 2> /dev/null & echo $!");
    }
}
