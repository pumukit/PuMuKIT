<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Menu;

interface ItemInterface
{
    public function getName(): string;

    public function getUri(): string;

    public function getAccessRole(): string;
}
