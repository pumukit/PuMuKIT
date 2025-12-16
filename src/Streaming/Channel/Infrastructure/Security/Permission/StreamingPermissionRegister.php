<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Infrastructure\Security\Permission;

use App\Shared\Domain\PermissionRegistryInterface;

final class StreamingPermissionRegister
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry
    ) {}

    public function register(): void
    {
        $this->registry->register(ChannelPermissions::all(), 'channel');
        $this->registry->register(ChannelUIPermissions::all(), 'channel');
    }
}
