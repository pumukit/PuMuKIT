<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\DependencyInjection;

use Pumukit\NewAdminBundle\EventListener\NakedBackofficeListener;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PumukitNewAdminExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('pumukit_new_admin.licenses', $config['licenses']);
        $container->setParameter('pumukit_new_admin.multimedia_object_label', $config['multimedia_object_label']);
        $container->setParameter('pumukit_new_admin.show_menu_place_and_precinct', $config['show_menu_place_and_precinct']);
        $container->setParameter('pumukit_new_admin.show_naked_pub_tab', $config['show_naked_pub_tab']);
        $container->setParameter('pumukit_new_admin.base_catalogue_tag', $config['base_catalogue_tag']);
        $container->setParameter('pumukit_new_admin.enable_playlist', $config['enable_playlist']);
        $container->setParameter('pumukit_new_admin.multimedia_object_archive_user_assign', $config['multimedia_object_archive_user_assign']);
        $container->setParameter('pumukit_new_admin.metadata_translators', []);

        if ($container->hasParameter('pumukit.naked_backoffice_domain')) {
            $arguments = [
                '%pumukit.naked_backoffice_domain%',
                '%pumukit.naked_backoffice_background%',
            ];

            if ($container->hasParameter('pumukit.naked_backoffice_color')) {
                $arguments[] = '%pumukit.naked_backoffice_color%';
            }

            if ($container->hasParameter('pumukit.naked_custom_css_url')) {
                $arguments[] = '%pumukit.naked_custom_css_url%';
            }

            $definition = new Definition(NakedBackofficeListener::class, $arguments);
            $definition->addTag('kernel.event_listener', ['event' => 'kernel.controller', 'method' => 'onKernelController']);
            $container->setDefinition('pumukitnewadmin.nakedbackoffice', $definition);
        }
    }
}
