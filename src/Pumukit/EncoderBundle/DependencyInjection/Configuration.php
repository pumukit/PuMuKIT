<?php

namespace Pumukit\EncoderBundle\DependencyInjection;

use Pumukit\EncoderBundle\Services\CpuService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pumukit_encoder');

        //Doc in http://symfony.com/doc/current/components/config/definition.html
        $this->addGlobalConfig($rootNode);
        $this->addCpusSection($rootNode);
        $this->addProfilesSection($rootNode);
        $this->addThumbnailSection($rootNode);
        $this->addTargetDefaultProfiles($rootNode);

        return $treeBuilder;
    }

    public static function addGlobalConfig(ArrayNodeDefinition $node)
    {
        $node
            ->children()
            ->booleanNode('delete_inbox_files')
            ->info('Delete imported inbox files')
            ->defaultValue(false)
            ->end()
            ;
    }

    /**
     * Adds `profiles` section.
     */
    public static function addProfilesSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
            ->arrayNode('profiles')
            ->normalizeKeys(false)
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->children()
            ->booleanNode('generate_pic')->defaultValue(true)
            ->info('When false, mmobj pics will not be generated from tracks generated using this profile')->end()
            ->booleanNode('nocheckduration')->defaultValue(false)
            ->info('When true, the usual duration checks are not performed on this profile.')->end()
            ->booleanNode('display')->defaultValue(false)
            ->info('Displays the track')->end()
            ->booleanNode('wizard')->defaultValue(true)
            ->info('Shown in wizard')->end()
            ->booleanNode('master')->defaultValue(true)
            ->info('The track is master copy')->end()
            ->booleanNode('downloadable')->defaultValue(false)
            ->info('The track generated is downloadable')->end()
                            //Used in JobGeneratorListener
            ->scalarNode('target')->defaultValue('')
            ->info('Profile is used to generate a new track when a multimedia object is tagged with a publication channel tag name with this value. List of names')->end()
            ->scalarNode('tags')->defaultValue('')->info('Tags used in tracks created with this profiles')->end()
            ->scalarNode('format')->info('Format of the track')->end()
            ->scalarNode('codec')->info('Codec of the track')->end()
            ->scalarNode('mime_type')->info('Mime Type of the track')->end()
            ->scalarNode('extension')->info('Extension of the track. If empty the input file extension is used.')->end()
                            //Used in JobGeneratorListener
            ->integerNode('resolution_hor')->min(0)->defaultValue(0)
            ->info('Horizontal resolution of the track, 0 if it depends from original video')->end()
                            //Used in JobGeneratorListener
            ->integerNode('resolution_ver')->min(0)->defaultValue(0)
            ->info('Vertical resolution of the track, 0 if it depends from original video')->end()
            ->scalarNode('bitrate')->info('Bit rate of the track')->end()
            ->scalarNode('framerate')->defaultValue('0')
            ->info('Framerate of the track')->end()
            ->integerNode('channels')->min(0)->defaultValue(1)
            ->info('Available Channels')->end()
                            //Used in JobGeneratorListener
            ->booleanNode('audio')->defaultValue(false)
            ->info('The track is only audio')->end()
            ->scalarNode('bat')->isRequired()->cannotBeEmpty()
            ->info('Command line to execute transcodification of track. Available variables: {{ input }}, {{ output }}, {{ tmpfile1 }}, {{ tmpfile2 }}, ... {{ tmpfile9 }}.')->end()
            ->scalarNode('file_cfg')->info('Configuration file')->end()
            ->arrayNode('streamserver')
            ->isRequired()
            ->children()
            ->scalarNode('name')->isRequired()->cannotBeEmpty()
            ->info('Name of the streamserver')->end()
            ->enumNode('type')
            ->values([ProfileService::STREAMSERVER_STORE, ProfileService::STREAMSERVER_DOWNLOAD,
                ProfileService::STREAMSERVER_WMV, ProfileService::STREAMSERVER_FMS, ProfileService::STREAMSERVER_RED5, ])
            ->isRequired()
            ->info('Streamserver type')->end()
            ->scalarNode('host')->isRequired()->cannotBeEmpty()
            ->info('Streamserver Hostname (or IP)')->end()
            ->scalarNode('description')->info('Streamserver host description')->end()
            ->scalarNode('dir_out')->isRequired()->cannotBeEmpty()
            ->info('Directory path of resulting track')->end()
            ->scalarNode('url_out')->info('URL of resulting track')->end()
            ->end()
            ->info('Type of streamserver for transcodification and data')->end()
            ->scalarNode('app')->isRequired()->cannotBeEmpty()
            ->info('Application to execute')->end()
            ->integerNode('rel_duration_size')->defaultValue(1)
            ->info('Relation between duration and size of track')->end()
            ->integerNode('rel_duration_trans')->defaultValue(1)
            ->info('Relation between duration and trans of track')->end()
            ->scalarNode('prescript')->info('Pre-script to execute')->end()
            ->end()
            ->end()
            ->end()
            ->end()
          ;
    }

    /**
     * Adds `cpu` section.
     */
    public function addCpusSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
            ->arrayNode('cpus')
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->children()
            ->scalarNode('host')->isRequired()->cannotBeEmpty()
            ->info('Encoder Hostnames (or IPs)')->end()
            ->integerNode('number')->min(0)->defaultValue(1)
                                //BC Delete in Pumukit 2.2
            ->info('Deprecated since version 2, to be removed in 2.2.')->end()
            ->integerNode('max')->min(0)->defaultValue(1)
            ->info('Top for the maximum number of concurrent encoding jobs')->end()
            ->enumNode('type')->values([CpuService::TYPE_LINUX, CpuService::TYPE_WINDOWS, CpuService::TYPE_GSTREAMER])
            ->defaultValue(CpuService::TYPE_LINUX)
            ->info('Type of the encoder host (linux, windows or gstreamer)')->end()
            ->scalarNode('user')
            ->info('Specifies the user to log in as on the remote encoder host')->end()
            ->scalarNode('password')
            ->info('Specifies the password to log in as on the remote encoder host')->end()
            ->scalarNode('description')->defaultValue('')
            ->info('Encoder host description')->end()
            ->arrayNode('profiles')
            ->beforeNormalization()->castToArray()->end()
            ->prototype('scalar')
            ->info('Array of profiles. If set, only the profiles listed will be transcoded here')
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    /**
     * Adds `thumbnail` section.
     */
    public function addThumbnailSection(ArrayNodeDefinition $node)
    {
        /** @var \Symfony\Component\Config\Definition\Builder\NodeBuilder */
        $aux = $node
            ->children()
            ->arrayNode('thumbnail')
            ->canBeUnset()
            ->children()
        ;

        $aux->integerNode('width')->defaultValue(768)
            ->info('Width resolution of thumbnail')->end();

        $aux->integerNode('height')->defaultValue(432)
            ->info('Height resolution of thumbnail')->end()
        ;
    }

    /**
     * Adds `target_default_profiles` section.
     */
    public function addTargetDefaultProfiles(ArrayNodeDefinition $node)
    {
        $node
            ->children()
            ->arrayNode('target_default_profiles')
            ->normalizeKeys(false)
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->children()
            ->scalarNode('audio')->defaultValue('')
            ->info('Default profile (or profiles) for an audio track')->end()
            ->scalarNode('video')->defaultValue('')
            ->info('Default profile (or profiles) for a video track')->end()
            ->end()
            ->end()
            ->end()
         ;
    }
}
