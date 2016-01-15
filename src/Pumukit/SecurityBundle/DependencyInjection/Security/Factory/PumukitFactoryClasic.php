<?php

namespace Pumukit\SecurityBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;

class PumukitFactory extends FormLoginFactory
{
  public function getKey()
  {
    return 'pumukit';
  }

}
