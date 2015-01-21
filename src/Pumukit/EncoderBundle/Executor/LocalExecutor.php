<?php

namespace Pumukit\EncoderBundle\Executor;

use Symfony\Component\Process\Process;

class LocalExecutor
{

  public function execute($command)
  {
      $process = new Process($command);
      $process->run();

      // executes after the command finishes
      if (!$process->isSuccessful()) {
          throw new \RuntimeException($process->getErrorOutput());
      }

      return $process->getOutput();
  }
}