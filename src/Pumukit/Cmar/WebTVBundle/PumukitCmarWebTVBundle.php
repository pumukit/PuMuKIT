<?php

namespace Pumukit\Cmar\WebTVBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PumukitCmarWebTVBundle extends Bundle
{
  public function getParent()
  {
    return 'PumukitWebTVBundle';
  }
}
