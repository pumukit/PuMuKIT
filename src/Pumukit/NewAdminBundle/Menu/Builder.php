<?php

namespace Pumukit\NewAdminBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class Builder.
 */
class Builder extends ContainerAware
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @param FactoryInterface $factory
     * @param array            $options
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        $this->authorizationChecker = $this->container->get('security.authorization_checker');

        $this->addDashboardMenu($menu);
        $this->addWizardMenu($menu);
        $this->addMediaManagerMenu($menu);
        $this->addStatsMenu($menu);
        $this->addLiveMenu($menu);
        $this->addJobMenu($menu);
        $this->addTablesMenu($menu);
        $this->addManagementMenu($menu);
        $this->addToolsMenu($menu);
        $this->addCustomMenu($menu);

        return $menu;
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu
     */
    protected function addDashboardMenu(\Knp\Menu\ItemInterface $menu)
    {
        $showDashboardTab = $this->container->getParameter('pumukit2.show_dashboard_tab');

        if ($showDashboardTab && $this->authorizationChecker->isGranted(Permission::ACCESS_DASHBOARD)) {
            $options = ['route' => 'pumukit_newadmin_dashboard_index', 'attributes' => ['class' => 'menu_dashboard']];
            $menu->addChild('Dashboard', $options);
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu
     */
    protected function addWizardMenu(\Knp\Menu\ItemInterface $menu)
    {
        if ($this->authorizationChecker->isGranted([Permission::ACCESS_WIZARD_UPLOAD] && $this->authorizationChecker->isGranted([Permission::SHOW_WIZARD_MENU]))) {
            if (!$this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
                $masterRequest = $this->container->get('request_stack')->getMasterRequest();
                $class = ($masterRequest && (0 === strpos($masterRequest->attributes->get('_route'), 'pumukitwizard_default_'))) ? 'active' : '';
                $class .= ' menu_wizard_upload_new_videos';
                $options = ['route' => 'pumukitwizard_default_series', 'attributes' => ['class' => $class]];
                $menu->addChild('Upload new videos', $options);
            }
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu
     */
    protected function addMediaManagerMenu(\Knp\Menu\ItemInterface $menu)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_MULTIMEDIA_SERIES) || $this->authorizationChecker->isGranted(Permission::ACCESS_EDIT_PLAYLIST)) {
            $options = ['attributes' => ['class' => 'menu_media_manager']];
            $mediaManager = $menu->addChild('Media Manager', $options);
            $this->addMediaManagerChildrenMenu($mediaManager);
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $mediaManager
     */
    protected function addMediaManagerChildrenMenu(\Knp\Menu\ItemInterface $mediaManager)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_MULTIMEDIA_SERIES)) {
            $options = ['route' => 'pumukitnewadmin_series_index', 'attributes' => ['class' => 'menu_series']];
            $mediaManager->addChild('Series', $options);

            $activeMmsListAll = $this->container->getParameter('pumukit2.show_mms_list_all_menu');
            if ($activeMmsListAll) {
                $options = ['route' => 'pumukitnewadmin_mms_indexall', 'attributes' => ['class' => 'menu_multimedia_object_all']];
                $mediaManager->addChild($this->container->getParameter('pumukit_new_admin.multimedia_object_label'), $options);
            }

            $options = ['route' => 'pumukitnewadmin_unesco_index', 'attributes' => ['class' => 'menu_tag_catalogue']];
            $unesco = $mediaManager->addChild('UNESCO catalogue', $options);
            $unesco->setDisplayChildren(false);
        }

        if ($this->authorizationChecker->isGranted(Permission::ACCESS_EDIT_PLAYLIST)) {
            $options = ['route' => 'pumukitnewadmin_playlist_index', 'attributes' => ['class' => 'menu_playlists_index']];
            $mediaManager->addChild('Moodle Playlists', $options);
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu
     */
    protected function addStatsMenu(\Knp\Menu\ItemInterface $menu)
    {
        if ($this->authorizationChecker->isGranted('ROLE_ACCESS_STATS')) {
            $options = ['attributes' => ['class' => 'menu_stats']];
            $stats = $menu->addChild('Stats', $options);

            $options = ['route' => 'pumukit_stats_series_index', 'attributes' => ['class' => 'menu_stats_series']];
            $stats->addChild('Series', $options);

            $options = ['route' => 'pumukit_stats_mmobj_index', 'attributes' => ['class' => 'menu_stats_multimedia_object']];
            $stats->addChild('Multimedia Objects', $options);

            //TODO: Use VOTERS: https://github.com/KnpLabs/KnpMenu/blob/master/doc/01-Basic-Menus.markdown#the-current-menu-item
            //Voters are a way to check if a menu item is the current one. Now we are just checking the routes and setting the Current element manually
            $route = $this->container->get('request_stack')->getMasterRequest()->attributes->get('_route');
            $statsRoutes = ['pumukit_stats_series_index', 'pumukit_stats_mmobj_index', 'pumukit_stats_series_index_id', 'pumukit_stats_mmobj_index_id', 'pumukit_stats_versions'];
            if (in_array($route, $statsRoutes)) {
                $stats->setCurrent(true);
            }
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu
     */
    protected function addLiveMenu(\Knp\Menu\ItemInterface $menu)
    {
        $advanceLiveEvent = $this->container->hasParameter('pumukit_new_admin.advance_live_event') ? $this->container->getParameter('pumukit_new_admin.advance_live_event') : false;
        $options = ['attributes' => ['class' => 'menu_live']];
        $live = $menu->addChild('Live management', $options);
        if ($advanceLiveEvent) {
            $this->addAdvancedLive($live);
        } else {
            $this->addBasicLive($live);
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $live
     */
    protected function addAdvancedLive(\Knp\Menu\ItemInterface $live)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_LIVE_EVENTS) || $this->authorizationChecker->isGranted(Permission::ACCESS_LIVE_CHANNELS)) {
            if ($this->authorizationChecker->isGranted(Permission::ACCESS_LIVE_EVENTS)) {
                $options = ['route' => 'pumukit_new_admin_live_event_index', 'attributes' => ['class' => 'menu_live_events']];
                $live->addChild('Live Events', $options);
            }
            if ($this->authorizationChecker->isGranted(Permission::ACCESS_LIVE_CHANNELS)) {
                $options = ['route' => 'pumukitnewadmin_live_index', 'attributes' => ['class' => 'menu_live_channels']];
                $live->addChild('Channel configuration', $options);
            }
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $live
     */
    protected function addBasicLive(\Knp\Menu\ItemInterface $live)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_LIVE_EVENTS) || $this->authorizationChecker->isGranted(Permission::ACCESS_LIVE_CHANNELS)) {
            if ($this->authorizationChecker->isGranted(Permission::ACCESS_LIVE_CHANNELS)) {
                $options = ['route' => 'pumukitnewadmin_live_index', 'attributes' => ['class' => 'menu_live_channels']];
                $live->addChild('Live Channels', $options);
            }
            if ($this->authorizationChecker->isGranted(Permission::ACCESS_LIVE_EVENTS)) {
                $options = ['route' => 'pumukitnewadmin_event_index', 'attributes' => ['class' => 'menu_live_events']];
                $live->addChild('Live Events', $options);
            }
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu
     */
    protected function addJobMenu(\Knp\Menu\ItemInterface $menu)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_JOBS)) {
            $options = ['route' => 'pumukit_encoder_info', 'attributes' => ['class' => 'menu_encoder']];
            $menu->addChild('Encoder jobs', $options);
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu
     */
    protected function addTablesMenu(\Knp\Menu\ItemInterface $menu)
    {
        if (($this->authorizationChecker->isGranted(Permission::ACCESS_PEOPLE) && $this->authorizationChecker->isGranted(Permission::SHOW_PEOPLE_MENU))
            || $this->authorizationChecker->isGranted(Permission::ACCESS_TAGS)
            || $this->authorizationChecker->isGranted(Permission::ACCESS_SERIES_TYPES)) {
            $options = ['attributes' => ['class' => 'menu_tables']];
            $tables = $menu->addChild('Tables', $options);

            $this->addPeopleMenu($tables);
            $this->addTagsMenu($tables);
            $this->addPlaceAndPrecinctMenu($tables);
            $this->addSeriesTypeMenu($tables);
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $tables
     */
    protected function addPeopleMenu(\Knp\Menu\ItemInterface $tables)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_PEOPLE) && $this->authorizationChecker->isGranted(Permission::SHOW_PEOPLE_MENU)) {
            $options = ['route' => 'pumukitnewadmin_person_index', 'attributes' => ['class' => 'menu_people']];
            $tables->addChild('People', $options);
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $tables
     */
    protected function addTagsMenu(\Knp\Menu\ItemInterface $tables)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_TAGS)) {
            $options = ['route' => 'pumukitnewadmin_tag_index', 'attributes' => ['class' => 'menu_tags']];
            $tables->addChild('Tags', $options);
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $tables
     */
    protected function addPlaceAndPrecinctMenu(\Knp\Menu\ItemInterface $tables)
    {
        $menuPlaceAndPrecinct = $this->container->hasParameter('pumukit_new_admin.show_menu_place_and_precinct') ? $this->container->getParameter('pumukit_new_admin.show_menu_place_and_precinct') : false;
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_TAGS) && $menuPlaceAndPrecinct) {
            $options = ['route' => 'pumukitnewadmin_places_index', 'attributes' => ['class' => 'menu_places_and_precinct']];
            $tables->addChild('Places and precinct', $options);
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $tables
     */
    protected function addSeriesTypeMenu(\Knp\Menu\ItemInterface $tables)
    {
        $showSeriesTypeTab = $this->container->hasParameter('pumukit2.use_series_channels') && $this->container->getParameter('pumukit2.use_series_channels');
        if ($showSeriesTypeTab && $this->authorizationChecker->isGranted(Permission::ACCESS_SERIES_TYPES)) {
            $options = ['route' => 'pumukitnewadmin_seriestype_index', 'attributes' => ['class' => 'menu_series_type']];
            $tables->addChild('Series types', $options);
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu
     */
    protected function addManagementMenu(\Knp\Menu\ItemInterface $menu)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_ADMIN_USERS)
            || $this->authorizationChecker->isGranted(Permission::ACCESS_GROUPS)
            || $this->authorizationChecker->isGranted(Permission::ACCESS_PERMISSION_PROFILES)
            || $this->authorizationChecker->isGranted(Permission::ACCESS_ROLES)) {
            $options = ['attributes' => ['class' => 'menu_management']];
            $management = $menu->addChild('Management', $options);

            $this->addAdminUsersMenu($management);
            $this->addGroupsMenu($management);
            $this->addPermissionProfilesMenu($management);
            $this->addRolesMenu($management);
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $management
     */
    protected function addAdminUsersMenu(\Knp\Menu\ItemInterface $management)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_ADMIN_USERS)) {
            $options = ['route' => 'pumukitnewadmin_user_index', 'attributes' => ['class' => 'menu_users']];
            $management->addChild('Admin users', $options);
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $management
     */
    protected function addGroupsMenu(\Knp\Menu\ItemInterface $management)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_GROUPS)) {
            $options = ['route' => 'pumukitnewadmin_group_index', 'attributes' => ['class' => 'menu_groups']];
            $management->addChild('Groups', $options);
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $management
     */
    protected function addPermissionProfilesMenu(\Knp\Menu\ItemInterface $management)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_PERMISSION_PROFILES)) {
            $options = ['route' => 'pumukitnewadmin_permissionprofile_index', 'attributes' => ['class' => 'menu_permission_profiles']];
            $management->addChild('Permission profiles', $options);
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $management
     */
    protected function addRolesMenu(\Knp\Menu\ItemInterface $management)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_ROLES)) {
            $options = ['route' => 'pumukitnewadmin_role_index', 'attributes' => ['class' => 'menu_roles']];
            $management->addChild('Roles', $options);
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu
     */
    protected function addToolsMenu(\Knp\Menu\ItemInterface $menu)
    {
        $showImporterTab = $this->container->hasParameter('pumukit_opencast.show_importer_tab') && $this->container->getParameter('pumukit_opencast.show_importer_tab');
        $tools = null;
        if ($showImporterTab && $this->authorizationChecker->isGranted('ROLE_ACCESS_IMPORTER')) {
            $options = ['attributes' => ['class' => 'menu_tools']];
            $tools = $menu->addChild('Tools', $options);

            $options = ['route' => 'pumukitopencast', 'attributes' => ['class' => 'menu_tools_opencast']];
            $tools->addChild('OC-Importer', $options);
        }

        if ($this->authorizationChecker->isGranted(Permission::ACCESS_SERIES_STYLE)) {
            if (!$tools) {
                $options = ['attributes' => ['class' => 'menu_tools']];
                $tools = $menu->addChild('Tools', $options);
            }

            $options = ['route' => 'pumukit_newadmin_series_styles', 'attributes' => ['class' => 'menu_series_styles']];
            $tools->addChild('Series style', $options);
        }

        foreach ($this->container->get('pumukitnewadmin.menu')->items() as $item) {
            $this->addDynamicToolMenu($menu, $item, $tools);
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface      $menu
     * @param ItemInterface                $item
     * @param \Knp\Menu\ItemInterface|null $tools
     */
    protected function addDynamicToolMenu(\Knp\Menu\ItemInterface $menu, ItemInterface $item, $tools)
    {
        if ($this->authorizationChecker->isGranted($item->getAccessRole())) {
            if (!$tools) {
                $options = ['attributes' => ['class' => 'menu_tools']];
                $tools = $menu->addChild('Tools', $options);
            }

            $class = 'menu_tools_'.strtolower(str_replace(' ', '_', $item->getName()));
            $options = ['route' => $item->getUri(), 'attributes' => ['class' => $class]];
            $tools->addChild($item->getName(), $options);
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu
     */
    protected function addCustomMenu(\Knp\Menu\ItemInterface $menu)
    {
        // NOTE: Override this function to add new item menu in PuMuKIT
    }
}
