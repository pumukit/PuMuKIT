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

      // executes after the command finishes
      if (!$process->isSuccessful()) {
          throw new \RuntimeException($process->getErrorOutput());
      }

      //TODO $process->getErrorOutput();

      return $process->getOutput();
  }
}