<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Executor;

interface ExecutorInterface
{
    public function execute(array|string $command, array $cpu): string;
}
