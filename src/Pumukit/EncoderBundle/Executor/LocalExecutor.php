<?php

namespace Pumukit\EncoderBundle\Executor;

use Symfony\Component\Process\Process;

class LocalExecutor
{

  public function execute($command, array $cpu=null)
  {
      $process = new Process($command);
      $process->setTimeout(null);
      $process->setIdleTimeout(null);
      $process->run();

      if (!$process->isSuccessful()) {
          throw new \RuntimeException($process->getErrorOutput());
      }

      //TODO mix strerr and strout.
      return sprintf("%s\n%s", $process->getOutput(), $process->getErrorOutput());
  }
}