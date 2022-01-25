<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\EventListener;

use Pumukit\CoreBundle\Event\InboxUploadEvent;
use Symfony\Component\Process\Process;

class InboxUploadListener
{
    private $inboxPath;
    private $kernelProjectDir;

    public function __construct(string $inboxPath, string $kernelProjectDir)
    {
        $this->inboxPath = $inboxPath;
        $this->kernelProjectDir = $kernelProjectDir;
    }

    public function autoImport(InboxUploadEvent $event): void
    {
        $urlUpload = $this->inboxPath.'/'.$event->getFileName();

        if (null !== $event->getFolder()) {
            $urlUpload = $this->inboxPath.'/'.$event->getFolder().'/'.$event->getFileName();
        }

        $command = [
            'php',
            $this->kernelProjectDir.'/'.'bin/console',
            'pumukit:import:inbox',
            $urlUpload,
        ];

        $process = new Process($command);
        $command = $process->getCommandLine();

        shell_exec("nohup {$command} 1> /dev/null 2> /dev/null & echo $!");
    }
}
