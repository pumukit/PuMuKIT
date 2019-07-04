<?php

namespace Pumukit\OpencastBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PumukitOpencastExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('pumukit_opencast.show_importer_tab', $config['show_importer_tab']);

        if (isset($config['host']) && $config['host']) {
            if (!filter_var($config['host'], FILTER_VALIDATE_URL)) {
                throw new InvalidConfigurationException(sprintf(
                    'The parameter "pumukit_opencast.host" is not a valid url: "%s" ',
                    $config['host']
                ));
            }

            $env = $container->getParameter('kernel.environment');

            if ('dev' !== $env) {
                foreach ($config['url_mapping'] as $m) {
                    if (!realpath($m['path'])) {
                        throw new \RuntimeException(sprintf(
                            'The "%s" directory does not exist. Check "pumukit_opencast.url_mapping".',
                            $m['path']
                    ));
                    }
                }
            }

            $container
                ->register('pumukit_opencast.client', 'Pumukit\\OpencastBundle\\Services\\ClientService')
                ->addArgument($config['host'])
                ->addArgument($config['username'])
                ->addArgument($config['password'])
                ->addArgument($config['player'])
                ->addArgument($config['scheduler'])
                ->addArgument($config['dashboard'])
                ->addArgument($config['delete_archive_mediapackage'])
                ->addArgument($config['deletion_workflow_name'])
                ->addArgument($config['manage_opencast_users'])
                ->addArgument(new Parameter('pumukit.insecure_http_client'))
                ->addArgument($config['admin_host'])
                ->addArgument(new Reference('logger'))
                ->addArgument(new Reference('security.role_hierarchy'))
            ;

            $container
                ->register('pumukit_opencast.job', 'Pumukit\\OpencastBundle\\Services\\OpencastService')
                ->addArgument(new Reference('pumukitencoder.job'))
                ->addArgument(new Reference('pumukitencoder.profile'))
                ->addArgument(new Reference('pumukitschema.multimedia_object'))
                ->addArgument($config['sbs'])
                ->addArgument($config['url_mapping'])
                ->addArgument(['opencast_host' => $config['host'], 'opencast_username' => $config['username'], 'opencast_password' => $config['password']])
                ->addArgument($config['error_if_file_not_exist'])
            ;

            $container
                ->register('pumukit_opencast.series_importer', 'Pumukit\\OpencastBundle\\Services\\SeriesImportService')
                ->addArgument(new Reference('doctrine_mongodb.odm.document_manager'))
                ->addArgument(new Reference('pumukitschema.factory'))
                ->addArgument(new Reference('pumukit_opencast.client'))
                ->addArgument(new Reference('pumukitschema.series_dispatcher'))
                ->addArgument(new Parameter('pumukit.locales'))
            ;

            $pumukitcustomlanguages = $container->hasParameter('pumukit.customlanguages') ?
                new Parameter('pumukit.customlanguages') :
                [];

            $container
                ->register('pumukit_opencast.import', 'Pumukit\\OpencastBundle\\Services\\OpencastImportService')
                ->addArgument(new Reference('doctrine_mongodb.odm.document_manager'))
                ->addArgument(new Reference('pumukitschema.factory'))
                ->addArgument(new Reference('pumukitschema.track'))
                ->addArgument(new Reference('pumukitschema.tag'))
                ->addArgument(new Reference('pumukitschema.multimedia_object'))
                ->addArgument(new Reference('pumukit_opencast.client'))
                ->addArgument(new Reference('pumukit_opencast.job'))
                ->addArgument(new Reference('pumukit.inspection'))
                ->addArgument(new Parameter('pumukit.locales'))
                ->addArgument(new Parameter('pumukit_opencast.default_tag_imported'))
                ->addArgument(new Reference('pumukit_opencast.series_importer'))
                ->addArgument($pumukitcustomlanguages)
            ;

            $container
                ->register('pumukit_opencast.workflow', 'Pumukit\\OpencastBundle\\Services\\WorkflowService')
                ->addArgument(new Reference('pumukit_opencast.client'))
                ->addArgument($config['delete_archive_mediapackage'])
                ->addArgument($config['deletion_workflow_name'])
            ;

            $container->setParameter('pumukit_opencast.sbs', $config['sbs']);
            $container->setParameter('pumukit_opencast.sbs.generate_sbs', $config['sbs']['generate_sbs'] ? $config['sbs']['generate_sbs'] : false);
            $container->setParameter('pumukit_opencast.sbs.profile', $config['sbs']['generate_sbs'] ? $config['sbs']['profile'] : null);
            $container->setParameter('pumukit_opencast.sbs.use_flavour', $config['sbs']['generate_sbs'] ? $config['sbs']['use_flavour'] : false);
            $container->setParameter('pumukit_opencast.sbs.flavour', $config['sbs']['use_flavour'] ? $config['sbs']['flavour'] : null);

            $container->setParameter('pumukit_opencast.use_redirect', $config['use_redirect']);
            $container->setParameter('pumukit_opencast.batchimport_inverted', $config['batchimport_inverted']);
            $container->setParameter('pumukit_opencast.delete_archive_mediapackage', $config['delete_archive_mediapackage']);
            $container->setParameter('pumukit_opencast.deletion_workflow_name', $config['deletion_workflow_name']);
            $container->setParameter('pumukit_opencast.url_mapping', $config['url_mapping']);
            $container->setParameter('pumukit_opencast.manage_opencast_users', $config['manage_opencast_users']);

            $container
                ->register('pumukit_opencast.remove_listener', 'Pumukit\\OpencastBundle\\EventListener\\RemoveListener')
                ->addArgument(new Reference('pumukit_opencast.client'))
                ->addArgument(new Reference('logger'))
                ->addArgument($config['delete_archive_mediapackage'])
                ->addArgument($config['deletion_workflow_name'])
                ->addTag('kernel.event_listener', ['event' => 'multimediaobject.delete', 'method' => 'onMultimediaObjectDelete'])
            ;

            $container
                ->register('pumukit_opencast.user_listener', 'Pumukit\\OpencastBundle\\EventListener\\UserListener')
                ->addArgument(new Reference('pumukit_opencast.client'))
                ->addArgument(new Reference('logger'))
                ->addArgument($config['manage_opencast_users'])
                ->addTag('kernel.event_listener', ['event' => 'user.create', 'method' => 'onUserCreate'])
                ->addTag('kernel.event_listener', ['event' => 'user.update', 'method' => 'onUserUpdate'])
                ->addTag('kernel.event_listener', ['event' => 'user.delete', 'method' => 'onUserDelete'])
            ;
        }

        $container->setParameter('pumukit_opencast.scheduler_on_menu', $config['scheduler_on_menu']);
        $container->setParameter('pumukit_opencast.host', $config['host']);
        $container->setParameter('pumukit_opencast.dashboard_on_menu', $config['dashboard_on_menu']);
        $container->setParameter('pumukit_opencast.default_tag_imported', $config['default_tag_imported']);

        $permissions = [['role' => 'ROLE_ACCESS_IMPORTER', 'description' => 'Access Importer']];
        $newPermissions = array_merge($container->getParameter('pumukitschema.external_permissions'), $permissions);
        $container->setParameter('pumukitschema.external_permissions', $newPermissions);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        if ($config['sync_series_with_opencast']) {
            $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
            $loader->load('serieslistener.xml');
        }
    }
}
