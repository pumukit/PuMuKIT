<?php

namespace Pumukit\OpencastBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;


/**
 * This is the class that loads and manages your bundle configuration
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

        if(isset($config['host']) && $config['host']) {
          if (!filter_var($config['host'], FILTER_VALIDATE_URL)){
            throw new InvalidConfigurationException(sprintf(
              'The parameter "pumukit_opencast.host" is not a valid url: "%s" ',
              $config['host']
            ));
          }

          $container
            ->register("pumukit_opencast.client", "Pumukit\OpencastBundle\Services\ClientService")
            ->addArgument($config['host'])
            ->addArgument($config['username'])
            ->addArgument($config['password'])
            ->addArgument($config['player']);

          $container
            ->register("pumukit_opencast.job", "Pumukit\OpencastBundle\Services\OpencastService")
            ->addArgument($config['generate_sbs'] ? $config['profile'] : null)
            ->addArgument(new Reference('pumukitencoder.job'))
            ->addArgument($config['url_mapping'])
            ->addArgument(array('opencast_host' => $config['host'], 'opencast_username' => $config['username'], 'opencast_password' => $config['password']));

          $container
            ->register("pumukit_opencast.import", "Pumukit\OpencastBundle\Services\OpencastImportService")
            ->addArgument(new Reference("doctrine_mongodb.odm.document_manager"))
            ->addArgument(new Reference("pumukitschema.factory"))
            ->addArgument(new Reference("pumukitschema.track"))
            ->addArgument(new Reference("pumukitschema.tag"))
            ->addArgument(new Reference("pumukit_opencast.client"))
            ->addArgument(new Reference("pumukit_opencast.job"))
            ->addArgument(new Reference("pumukit.inspection"))
            ->addArgument(new Parameter("pumukit2.locales"));
        }

        $container->setParameter('pumukit_opencast.generate_sbs', $config['generate_sbs'] ? $config['generate_sbs'] : false);
        $container->setParameter('pumukit_opencast.profile', $config['generate_sbs'] ? $config['profile'] : null);
        $container->setParameter('pumukit_opencast.scheduler_on_menu', $config['scheduler_on_menu']);
        $container->setParameter('pumukit_opencast.dashboard_on_menu', $config['dashboard_on_menu']);
    }
}
