<?php

namespace Pumukit\NotificationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    const TEMPLATE = 'PumukitNotificationBundle:Email:job.html.twig';
    const SENDER_EMAIL = 'notifications@pumukit.org';
    const ADMIN_EMAIL = 'admin@pumukit.org';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pumukit_notification');

        $rootNode
            ->children()
            ->booleanNode('enable')
            ->defaultFalse()
            ->info('Enable notifications to send emails')
            ->end()
            ->scalarNode('platform_name')
            ->defaultValue('Pumukit')
            ->info('The name of the Pumukit platform')
            ->end()
            ->scalarNode('sender_email')
            ->defaultValue(self::SENDER_EMAIL)
            ->info('The email of the sender')
            ->end()
            ->scalarNode('sender_name')
            ->defaultValue('Pumukit Notification Bundle')
            ->info('The name of the sender')
            ->end()
            ->booleanNode('enable_multi_lang')
            ->defaultFalse()
            ->info('Enable sending mails in multiple langs (subject and body)')
            ->end()
            ->scalarNode('template')
            ->defaultValue(self::TEMPLATE)
            ->info('Template of emails.')
            ->end()
            ->scalarNode('subject_success')
            ->defaultValue('Job success')
            ->info('Subject of email')
            ->end()
            ->scalarNode('subject_fails')
            ->defaultValue('Job fails')
            ->info('Subject of email fails')
            ->end()
            ->arrayNode('subject_success_trans')
            ->requiresAtLeastOneElement()
            ->info('subject success of email in multiple languages')
            ->prototype('array')
            ->children()
            ->scalarNode('locale')->isRequired()->defaultValue('en')->end()
            ->scalarNode('subject')->isRequired()->defaultValue('Subject Success')->end()
            ->end()
            ->end()
            ->end()
            ->arrayNode('subject_fails_trans')
            ->requiresAtLeastOneElement()
            ->info('subject fail of email in multiple languages')
            ->prototype('array')
            ->children()
            ->scalarNode('locale')->isRequired()->defaultValue('en')->end()
            ->scalarNode('subject')->isRequired()->defaultValue('Subject Fails')->end()
            ->end()
            ->end()
            ->end()
            ->booleanNode('notificate_errors_to_admin')
            ->defaultTrue()
            ->info('Whether the admin email receives error notifications or not')
            ->end()
            ->arrayNode('admin_email')
            ->prototype('scalar')
            ->defaultValue(self::ADMIN_EMAIL)
            ->info('Email or list of emails of the administrators of the platform.')
            ->end()
            ->end()
          ;

        return $treeBuilder;
    }
}
