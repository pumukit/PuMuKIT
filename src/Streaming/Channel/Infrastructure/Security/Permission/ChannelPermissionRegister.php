<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Infrastructure\Security\Permission;

use App\Shared\Domain\PermissionRegistryInterface;
use App\Shared\Infrastructure\Security\Permission\ValueObject\PermissionType;

final class ChannelPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {}

    public function register(): void
    {
        $this->registry->register(ChannelPermissions::all(), 'channel', PermissionType::DOMAIN);
        $this->registry->register(ChannelUIPermissions::all(), 'channel', PermissionType::UI);
    }
}
