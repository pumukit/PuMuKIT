<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Executor;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class LocalExecutor
{
    public function execute($command, ?array $cpu = null): string
    {
        $fs = new Filesystem();

        $tempFile = tempnam(sys_get_temp_dir(), '');
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        $fs->mkdir($tempFile);

        if (is_string($command)) {
            $process = Process::fromShellCommandline($command);
        } else {
            $process = new Process($command, $tempFile);
        }
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->run();

        $fs->remove($tempFile);

        if (!$process->isSuccessful()) {
            throw new ExecutorException($process->getErrorOutput());
        }

        return sprintf("%s\n%s", $process->getOutput(), $process->getErrorOutput());
    }
}
