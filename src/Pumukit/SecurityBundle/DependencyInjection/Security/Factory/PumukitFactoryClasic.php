<?php

namespace Pumukit\SecurityBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;

class PumukitFactoryClasic extends FormLoginFactory
{
    public function getKey()
    {
        return 'pumukit';
    }
}
