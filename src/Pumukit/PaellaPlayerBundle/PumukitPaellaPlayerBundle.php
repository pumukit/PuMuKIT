<?php

namespace Pumukit\PaellaPlayerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PumukitPaellaPlayerBundle extends Bundle
{
    public function getParent()
    {
        return 'PumukitBasePlayerBundle';
    }
}
