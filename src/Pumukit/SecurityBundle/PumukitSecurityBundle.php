<?php

namespace Pumukit\SecurityBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Pumukit\SecurityBundle\DependencyInjection\Security\Factory\PumukitFactory;

class PumukitSecurityBundle extends Bundle
{
  public function build(ContainerBuilder $container)
  {
    parent::build($container);

    $extension = $container->getExtension('security');
    $extension->addSecurityListenerFactory(new PumukitFactory());
  }
}
