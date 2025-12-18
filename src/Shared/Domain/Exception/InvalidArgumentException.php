<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

use Exception;

class InvalidArgumentException extends Exception
{
    public function __construct(string $message = "Invalid argument provided.", int $code = 400, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
