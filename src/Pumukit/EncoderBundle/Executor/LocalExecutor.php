<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Executor;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class LocalExecutor
{
    public function execute($command, array $cpu = null)
    {
        $fs = new Filesystem();

        $tempfile = tempnam(sys_get_temp_dir(), '');
        if (file_exists($tempfile)) {
            unlink($tempfile);
        }
        $fs->mkdir($tempfile);

        $process = new Process($command, $tempfile);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->run();

        $fs->remove($tempfile);

        if (!$process->isSuccessful()) {
            throw new ExecutorException($process->getErrorOutput());
        }

        return sprintf("%s\n%s", $process->getOutput(), $process->getErrorOutput());
    }
}
