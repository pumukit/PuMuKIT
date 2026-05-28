<?php

declare(strict_types=1);

namespace Pumukit\PaellaPlayerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PumukitPaellaPlayerBundle extends Bundle
{
    private const PAELLA_REPOSITORY_PATH = 'paellarepository';

    public static function paellaRepositoryPath(): string
    {
        return self::PAELLA_REPOSITORY_PATH;
    }
}
