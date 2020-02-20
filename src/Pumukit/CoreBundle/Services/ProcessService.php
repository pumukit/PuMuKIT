<?php

namespace Pumukit\CoreBundle\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class ProcessService
{
    protected const DEFAULT_TIMEOUT_PROCESS = 60;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function createAndRunProcess($arguments, ?string $workingDirectory = null): Process
    {
        $process = $this->createProcess($arguments, $workingDirectory);

        return $this->runProcess($process);
    }

    public function createAndRunProcessInBackground($arguments, ?string $workingDirectory = null): void
    {
        $process = $this->createProcess($arguments, $workingDirectory);
        $this->runProcessInBackground($process);
    }

    public function createProcess(array $arguments, ?string $workingDirectory = null): Process
    {
        return new Process($arguments, $workingDirectory);
    }

    public function createProcessFromShellCommandLine(string $command, ?string $workingDirectory = null): Process
    {
        return Process::fromShellCommandline($command, $workingDirectory);
    }

    public function runProcess(Process $process, int $timeout = self::DEFAULT_TIMEOUT_PROCESS, bool $useLog = true): Process
    {
        $this->logProcess($process, $useLog);
        $process->setTimeout($timeout);
        $process->run();

        return $process;
    }

    public function runProcessInBackground(Process $process, bool $useLog = true): void
    {
        $this->logProcess($process, $useLog);
        $command = $process->getCommandLine();
        shell_exec("nohup {$command} 1> /dev/null 2> /dev/null & echo $!");
    }

    public function logProcess(Process $process, bool $useLog = true): void
    {
        if ($useLog) {
            $this->logger->info($process->getCommandLine());
        }
    }
}
