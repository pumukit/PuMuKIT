<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\Exception;

final class PathException extends \Exception
{
    public function __construct($message = '', $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
