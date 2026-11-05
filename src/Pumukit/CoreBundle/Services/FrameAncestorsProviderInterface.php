<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Services;

interface FrameAncestorsProviderInterface
{
    public function frameAncestors(): array;
}
