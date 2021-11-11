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
        $command = [
            'php',
            $this->kernelProjectDir.'/'.'bin/console',
            'import:inbox',
            $this->inboxPath.'/'.$event->getFolder().'/'.$event->getFileName(),
        ];

        $process = new Process($command);
        $command = $process->getCommandLine();

        shell_exec("nohup {$command} 1> /dev/null 2> /dev/null & echo $!");
    }
}
