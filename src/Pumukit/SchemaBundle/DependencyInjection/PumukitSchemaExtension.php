<?php

namespace Pumukit\SchemaBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategy;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PumukitSchemaExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('pumukitschema.default_copyright', $config['default_copyright']);
        $container->setParameter('pumukitschema.default_license', $config['default_license']);
        $container->setParameter('pumukitschema.default_series_pic', $config['default_series_pic']);
        $container->setParameter('pumukitschema.default_playlist_pic', $config['default_playlist_pic']);
        $container->setParameter('pumukitschema.default_video_pic', $config['default_video_pic']);
        $container->setParameter('pumukitschema.default_audio_hd_pic', $config['default_audio_hd_pic']);
        $container->setParameter('pumukitschema.default_audio_sd_pic', $config['default_audio_sd_pic']);
        $container->setParameter('pumukitschema.personal_scope_role_code', $config['personal_scope_role_code']);
        $container->setParameter('pumukitschema.enable_add_user_as_person', $config['enable_add_user_as_person']);
        $container->setParameter('pumukitschema.personal_scope_delete_owners', $config['personal_scope_delete_owners']);
        $container->setParameter('pumukitschema.external_permissions', $config['external_permissions']);
        $container->setParameter('pumukitschema.gen_user_salt', $config['gen_user_salt']);

        // To use with CAS (rewrite session_id with the CAS ticket)
        $container->setParameter('security.authentication.session_strategy.strategy', SessionAuthenticationStrategy::NONE);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
