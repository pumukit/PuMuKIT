<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\EventListener;

use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Event\UploadFileEvent;
use Symfony\Component\Process\Process;

class ServerUploadListener
{
    private string $kernelProjectDir;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, string $kernelProjectDir)
    {
        $this->kernelProjectDir = $kernelProjectDir;
        $this->logger = $logger;
    }

    public function autoImport(UploadFileEvent $event): void
    {
        $command = [
            'php',
            $this->kernelProjectDir.'/bin/console',
            'pumukit:import:inbox',
            $event->getFileName(),
            '--user='.$event->getUser()->getUsername(),
            '--series='.$event->getSeries(),
            '--profile='.$event->getProfile(),
        ];

        $process = new Process($command);
        $command = $process->getCommandLine();

        $this->logger->info($process->getCommandLine());

        shell_exec("nohup {$command} 1> /dev/null 2> /dev/null & echo $!");
    }
}
