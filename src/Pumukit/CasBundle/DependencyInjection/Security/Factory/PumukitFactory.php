<?php

namespace Pumukit\CasBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class PumukitFactory.
 */
class PumukitFactory extends AbstractFactory
{
    public function __construct()
    {
        $this->addOption('check_path', '/cas/login');
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return 'pre_auth';
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return 'pumukit';
    }

    /**
     * {@inheritdoc}
     */
    protected function isRememberMeAware($config)
    {
        return false;
    }

    protected function getListenerId()
    {
        return 'pumukit.security.authentication.listener';
    }

    protected function createListener($container, $id, $config, $userProvider)
    {
        $listenerId = parent::createListener($container, $id, $config, $userProvider);

        $container
            ->getDefinition($listenerId)
            ->addArgument(new Reference('pumukit.casservice'))
        ;

        return $listenerId;
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $provider = 'pumukit.security.authentication.provider.'.$id;

        $container
            ->setDefinition($provider, new ChildDefinition('pumukit.security.authentication.provider'))
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(1, $id)
            ;

        return $provider;
    }

    protected function createEntryPoint($container, $id, $config, $defaultEntryPoint)
    {
        $entryPointId = 'security.authentication.form_entry_point.'.$id;
        $container
            ->setDefinition($entryPointId, new ChildDefinition('security.authentication.form_entry_point'))
            ->addArgument(new Reference('security.http_utils'))
            ->addArgument($config['check_path'])
            ->addArgument($config['use_forward'])
            ;

        return $entryPointId;
    }
}
