<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security\ValueObject;

enum PermissionType: string
{
    case DOMAIN = 'domain';
    case UI     = 'ui';
}
