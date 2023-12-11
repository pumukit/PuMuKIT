<?php

declare(strict_types=1);

namespace App\Pumukit\SchemaBundle\Document\Exception;

final class UrlException extends \Exception
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
