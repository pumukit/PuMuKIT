<?php

namespace Pumukit\JWPlayerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PumukitJWPlayerBundle extends Bundle
{
    public function getParent()
    {
        return 'PumukitBasePlayerBundle';
    }
}
