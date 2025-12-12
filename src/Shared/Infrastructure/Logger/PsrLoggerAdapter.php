<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Logger;

use App\Shared\Domain\LoggerInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

final class PsrLoggerAdapter implements LoggerInterface
{
    public function __construct(
        private PsrLoggerInterface $psrLogger
    ) {}

    public function emergency(string $message, array $context = []): void
    {
        $this->psrLogger->emergency($message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->psrLogger->alert($message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->psrLogger->critical($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->psrLogger->error($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->psrLogger->warning($message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->psrLogger->notice($message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->psrLogger->info($message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->psrLogger->debug($message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $this->psrLogger->log($level, $message, $context);
    }
}
