<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Executor;

use Pumukit\CoreBundle\Utils\FileSystemUtils;
use Symfony\Component\Process\Process;

class LocalExecutor
{
    public function execute($command, array $cpu = null): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), '');
        if (FileSystemUtils::exists($tempFile)) {
            FileSystemUtils::remove($tempFile);
        }

        FileSystemUtils::createFolder($tempFile);

        if (is_string($command)) {
            $process = Process::fromShellCommandline($command);
        } else {
            $process = new Process($command, $tempFile);
        }
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->run();

        FileSystemUtils::remove($tempFile);

        if (!$process->isSuccessful()) {
            throw new ExecutorException($process->getErrorOutput());
        }

        return sprintf("%s\n%s", $process->getOutput(), $process->getErrorOutput());
    }
}
