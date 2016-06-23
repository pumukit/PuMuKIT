<?php

namespace Pumukit\NewAdminBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Pumukit\SchemaBundle\Security\Permission;

class Builder extends ContainerAware
{
    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        // Add translations in src/Pumukit/NewAdminBundle/Resource/translations/NewAdminBundle.locale.yml
        $authorizationChecker = $this->container->get('security.authorization_checker');
        $showImporterTab = $this->container->hasParameter('pumukit_opencast.show_importer_tab') && $this->container->getParameter('pumukit_opencast.show_importer_tab');
        $showDashboardTab = $this->container->getParameter('pumukit2.show_dashboard_tab');
        $showSeriesTypeTab = $this->container->hasParameter('pumukit2.use_series_channels') && $this->container->getParameter('pumukit2.use_series_channels');

        if ($showDashboardTab && false !== $authorizationChecker->isGranted(Permission::ACCESS_DASHBOARD)) {
            $menu->addChild('Dashboard', array('route' => 'pumukit_newadmin_dashboard_index'))->setExtra('translation_domain', 'NewAdminBundle');
        }

        if ($authorizationChecker->isGranted(Permission::ACCESS_MULTIMEDIA_SERIES)) {
            $mediaManager = $menu->addChild('Media Manager')->setExtra('translation_domain', 'NewAdminBundle');
            $series = $mediaManager->addChild('Series', array('route' => 'pumukitnewadmin_series_index'))->setExtra('translation_domain', 'NewAdminBundle');
            $playlists = $mediaManager->addChild('Moodle Playlists', array('route' => 'pumukitnewadmin_playlist_index'))->setExtra('translation_domain', 'NewAdminBundle');
            $series->addChild('Multimedia', array('route' => 'pumukitnewadmin_mms_index'))->setExtra('translation_domain', 'NewAdminBundle');
            $series->setDisplayChildren(false);
        }

        if ($authorizationChecker->isGranted('ROLE_ACCESS_STATS')) {
            $stats = $menu->addChild('Stats')->setExtra('translation_domain', 'NewAdminBundle');
            $stats->addChild('Series', array('route' => 'pumukit_stats_series_index'))->setExtra('translation_domain', 'NewAdminBundle');
            $stats->addChild('Multimedia Objects', array('route' => 'pumukit_stats_mmobj_index'))->setExtra('translation_domain', 'NewAdminBundle');

            //TODO: Use VOTERS: https://github.com/KnpLabs/KnpMenu/blob/master/doc/01-Basic-Menus.markdown#the-current-menu-item
            //Voters are a way to check if a menu item is the current one. Now we are just checking the routes and setting the Current element manually
            $route = $this->container->get('request_stack')->getMasterRequest()->attributes->get('_route');
            $statsRoutes = array('pumukit_stats_series_index', 'pumukit_stats_mmobj_index', 'pumukit_stats_series_index_id', 'pumukit_stats_mmobj_index_id');
            if(in_array($route, $statsRoutes)) {
                $stats->setCurrent(true);
            }
        }

        if ($authorizationChecker->isGranted(Permission::ACCESS_LIVE_EVENTS) || $authorizationChecker->isGranted(Permission::ACCESS_LIVE_CHANNELS)) {
            $live = $menu->addChild('Live')->setExtra('translation_domain', 'NewAdminBundle');
            if ($authorizationChecker->isGranted(Permission::ACCESS_LIVE_CHANNELS)) {
                $live->addChild('Live Channels', array('route' => 'pumukitnewadmin_live_index'))->setExtra('translation_domain', 'NewAdminBundle');
            }
            if ($authorizationChecker->isGranted(Permission::ACCESS_LIVE_EVENTS)) {
                $live->addChild('Live Events', array('route' => 'pumukitnewadmin_event_index'))->setExtra('translation_domain', 'NewAdminBundle');
            }
        }
        if ($authorizationChecker->isGranted(Permission::ACCESS_JOBS)) {
            $menu->addChild('Encoder jobs', array('route' => 'pumukit_encoder_info'))->setExtra('translation_domain', 'NewAdminBundle');
        }
        if ($authorizationChecker->isGranted(Permission::ACCESS_PEOPLE) || $authorizationChecker->isGranted(Permission::ACCESS_TAGS) ||
            $authorizationChecker->isGranted(Permission::ACCESS_SERIES_TYPES)) {
            $tables = $menu->addChild('Tables')->setExtra('translation_domain', 'NewAdminBundle');
            if ($authorizationChecker->isGranted(Permission::ACCESS_PEOPLE)) {
                $tables->addChild('People', array('route' => 'pumukitnewadmin_person_index'))->setExtra('translation_domain', 'NewAdminBundle');
            }
            if ($authorizationChecker->isGranted(Permission::ACCESS_TAGS)) {
                $tables->addChild('Tags', array('route' => 'pumukitnewadmin_tag_index'))->setExtra('translation_domain', 'NewAdminBundle');
            }
            if ($showSeriesTypeTab && $authorizationChecker->isGranted(Permission::ACCESS_SERIES_TYPES)) {
                $tables->addChild('Series types', array('route' => 'pumukitnewadmin_seriestype_index'))->setExtra('translation_domain', 'NewAdminBundle');
            }
        }

        if ($authorizationChecker->isGranted(Permission::ACCESS_ADMIN_USERS) || $authorizationChecker->isGranted(Permission::ACCESS_PERMISSION_PROFILES) ||
            $authorizationChecker->isGranted(Permission::ACCESS_ROLES)) {
            $management = $menu->addChild('Management')->setExtra('translation_domain', 'NewAdminBundle');
            if ($authorizationChecker->isGranted(Permission::ACCESS_ADMIN_USERS)) {
                $management->addChild('Admin users', array('route' => 'pumukitnewadmin_user_index'))->setExtra('translation_domain', 'NewAdminBundle');
            }
            if ($authorizationChecker->isGranted(Permission::ACCESS_GROUPS)) {
                $management->addChild('Groups', array('route' => 'pumukitnewadmin_group_index'))->setExtra('translation_domain', 'NewAdminBundle');
            }
            if ($authorizationChecker->isGranted(Permission::ACCESS_PERMISSION_PROFILES)) {
                $management->addChild('Permission profiles', array('route' => 'pumukitnewadmin_permissionprofile_index'))->setExtra('translation_domain', 'NewAdminBundle');
            }
            if ($authorizationChecker->isGranted(Permission::ACCESS_ROLES)) {
                $management->addChild('Roles', array('route' => 'pumukitnewadmin_role_index'))->setExtra('translation_domain', 'NewAdminBundle');
            }
        }

        if ($showImporterTab && $authorizationChecker->isGranted('ROLE_ACCESS_IMPORTER')) {
            $importer = $menu->addChild('Tools')->setExtra('translation_domain', 'NewAdminBundle');
            $importer->addChild('OC-Importer', array('route' => 'pumukitopencast'))->setExtra('translation_domain', 'NewAdminBundle');
        }

        return $menu;
    }
}
