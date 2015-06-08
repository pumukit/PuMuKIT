<?php

namespace Pumukit\Cmar\LiveBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PumukitCmarLiveBundle extends Bundle
{
    public function getParent()
    {
        return 'PumukitLiveBundle';
    }
}
