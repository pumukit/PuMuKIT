<?php

namespace Pumukit\WorkflowBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PumukitWorkflowExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('pumukit_workflow.auto_extract_pic', $config['auto_extract_pic']);
        $container->setParameter('pumukit_workflow.auto_extract_pic_percentage', $config['auto_extract_pic_percentage']);
        $container->setParameter('pumukit_workflow.dynamic_pic_extract', $config['dynamic_pic_extract']);
        $container->setParameter('pumukit_workflow.dynamic_pic_extract_track_tag_allowed', $config['dynamic_pic_extract_track_tag_allowed']);
    }
}
