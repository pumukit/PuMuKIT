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
        $container->setParameter('pumukit_schema.multimedia_object_add_owner_subject', $config['multimedia_object_add_owner_subject']);
        $container->setParameter('pumukit_schema.multimedia_object_add_owner_template', $config['multimedia_object_add_owner_template']);
        $container->setParameter('pumukit_schema.send_email_on_user_added_as_owner', $config['send_email_on_user_added_as_owner']);
        $container->setParameter('pumukit_schema.user_can_reject_owner_of_multimedia_object', $config['user_can_reject_owner_of_multimedia_object']);
        $container->setParameter('pumukit_schema.default_head_video', $config['default_head_video']);
        $container->setParameter('pumukit_schema.default_tail_video', $config['default_tail_video']);

        // To use with CAS (rewrite session_id with the CAS ticket)
        $container->setParameter('security.authentication.session_strategy.strategy', SessionAuthenticationStrategy::NONE);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
