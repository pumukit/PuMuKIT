<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Exception;

use Symfony\Component\HttpFoundation\File\Exception\FileException;

final class FileNotValid extends FileException
{
    public function __construct($message = '', $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
