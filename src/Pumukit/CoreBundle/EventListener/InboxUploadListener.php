<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\EventListener;

use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Event\UploadFileEvent;
use Pumukit\CoreBundle\Services\InboxService;
use Symfony\Component\Process\Process;

class InboxUploadListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly InboxService $inboxService,
        private readonly string $kernelProjectDir,
    ) {}

    public function autoImport(UploadFileEvent $event): void
    {
        $filePath = $this->inboxService->uploadedFilePath($event->getFileName(), $event->getSeries());
        $series = $event->getSeries();

        $command = [
            'php',
            $this->kernelProjectDir.'/bin/console',
            'pumukit:import:inbox',
            $filePath,
            '--user='.$event->getUser()->getUsername(),
            '--series='.$series,
            '--encoding-profile='.$event->getProfile(),
        ];

        $process = new Process($command);
        $command = $process->getCommandLine();

        $this->logger->warning($process->getCommandLine());

        shell_exec("nohup {$command} 1> /dev/null 2> /dev/null & echo $!");
    }
}
