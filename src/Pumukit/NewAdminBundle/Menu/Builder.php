<?php

namespace Pumukit\NewAdminBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface as KnpItemInterface;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class Builder.
 */
class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @param FactoryInterface $factory
     * @param array            $options
     *
     * @return KnpItemInterface
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
     * @param KnpItemInterface $menu
     */
    protected function addDashboardMenu(KnpItemInterface $menu)
    {
        $showDashboardTab = $this->container->getParameter('pumukit.show_dashboard_tab');

        if ($showDashboardTab && $this->authorizationChecker->isGranted(Permission::ACCESS_DASHBOARD)) {
            $options = ['route' => 'pumukit_newadmin_dashboard_index', 'attributes' => ['class' => 'menu_dashboard']];
            $menu->addChild('Dashboard', $options);
        }
    }

    /**
     * @param KnpItemInterface $menu
     */
    protected function addWizardMenu(KnpItemInterface $menu)
    {
        if (!$this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
            if ($this->authorizationChecker->isGranted(Permission::ACCESS_WIZARD_UPLOAD) && $this->authorizationChecker->isGranted(Permission::SHOW_WIZARD_MENU)) {
                $masterRequest = $this->container->get('request_stack')->getMasterRequest();
                $class = ($masterRequest && (0 === strpos($masterRequest->attributes->get('_route'), 'pumukitwizard_default_'))) ? 'active' : '';
                $class .= ' menu_wizard_upload_new_videos';
                $options = ['route' => 'pumukitwizard_default_series', 'attributes' => ['class' => $class]];
                $menu->addChild('Upload new videos', $options);
            }
        }
    }

    /**
     * @param KnpItemInterface $menu
     */
    protected function addMediaManagerMenu(KnpItemInterface $menu)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_MULTIMEDIA_SERIES) || $this->authorizationChecker->isGranted(Permission::ACCESS_EDIT_PLAYLIST)) {
            $options = ['attributes' => ['class' => 'menu_media_manager']];
            $mediaManager = $menu->addChild('Media Manager', $options);
            $this->addMediaManagerChildrenMenu($mediaManager);
        }
    }

    /**
     * @param KnpItemInterface $mediaManager
     */
    protected function addMediaManagerChildrenMenu(KnpItemInterface $mediaManager)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_MULTIMEDIA_SERIES)) {
            $options = ['route' => 'pumukitnewadmin_series_index', 'attributes' => ['class' => 'menu_series']];
            $series = $mediaManager->addChild('Series', $options);
            $series->addChild('Multimedia', ['route' => 'pumukitnewadmin_mms_index', 'attributes' => ['class' => 'menu_series_mms']]);
            $series->setDisplayChildren(false);

            $activeMmsListAll = $this->container->getParameter('pumukit.show_mms_list_all_menu');
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
     * @param KnpItemInterface $menu
     */
    protected function addStatsMenu(KnpItemInterface $menu)
    {
        if ($this->authorizationChecker->isGranted('ROLE_ACCESS_STATS')) {
            $options = ['attributes' => ['class' => 'menu_stats']];
            $stats = $menu->addChild('Stats', $options);

            $options = ['route' => 'pumukit_stats_series_index', 'attributes' => ['class' => 'menu_stats_series']];
            $stats->addChild('Series', $options);

            $options = ['route' => 'pumukit_stats_mmobj_index', 'attributes' => ['class' => 'menu_stats_multimedia_object']];
            $stats->addChild('Multimedia Objects', $options);

            //Voters are a way to check if a menu item is the current one. Now we are just checking the routes and setting the Current element manually
            $route = $this->container->get('request_stack')->getMasterRequest()->attributes->get('_route');
            $statsRoutes = ['pumukit_stats_series_index', 'pumukit_stats_mmobj_index', 'pumukit_stats_series_index_id', 'pumukit_stats_mmobj_index_id', 'pumukit_stats_versions'];
            if (in_array($route, $statsRoutes)) {
                $stats->setCurrent(true);
            }
        }
    }

    /**
     * @param KnpItemInterface $menu
     */
    protected function addLiveMenu(KnpItemInterface $menu)
    {
        $advanceLiveEvent = $this->container->hasParameter('pumukit_new_admin.advance_live_event') ? $this->container->getParameter('pumukit_new_admin.advance_live_event') : false;
        $options = ['attributes' => ['class' => 'menu_live']];
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_LIVE_EVENTS) || $this->authorizationChecker->isGranted(Permission::ACCESS_LIVE_CHANNELS)) {
            $live = $menu->addChild('Live management', $options);
            if ($advanceLiveEvent) {
                $this->addAdvancedLive($live);
            } else {
                $this->addBasicLive($live);
            }
        }
    }

    /**
     * @param KnpItemInterface $live
     */
    protected function addAdvancedLive(KnpItemInterface $live)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_LIVE_EVENTS)) {
            $options = ['route' => 'pumukit_new_admin_live_event_index', 'attributes' => ['class' => 'menu_live_events']];
            $live->addChild('Live Events', $options);
        }
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_LIVE_CHANNELS)) {
            $options = ['route' => 'pumukitnewadmin_live_index', 'attributes' => ['class' => 'menu_live_channels']];
            $live->addChild('Channel configuration', $options);
        }
    }

    /**
     * @param KnpItemInterface $live
     */
    protected function addBasicLive(KnpItemInterface $live)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_LIVE_CHANNELS)) {
            $options = ['route' => 'pumukitnewadmin_live_index', 'attributes' => ['class' => 'menu_live_channels']];
            $live->addChild('Live Channels', $options);
        }
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_LIVE_EVENTS)) {
            $options = ['route' => 'pumukitnewadmin_event_index', 'attributes' => ['class' => 'menu_live_events']];
            $live->addChild('Live Events', $options);
        }
    }

    /**
     * @param KnpItemInterface $menu
     */
    protected function addJobMenu(KnpItemInterface $menu)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_JOBS)) {
            $options = ['route' => 'pumukit_encoder_info', 'attributes' => ['class' => 'menu_encoder']];
            $menu->addChild('Encoder jobs', $options);
        }
    }

    /**
     * @param KnpItemInterface $menu
     */
    protected function addTablesMenu(KnpItemInterface $menu)
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
     * @param KnpItemInterface $tables
     */
    protected function addPeopleMenu(KnpItemInterface $tables)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_PEOPLE) && $this->authorizationChecker->isGranted(Permission::SHOW_PEOPLE_MENU)) {
            $options = ['route' => 'pumukitnewadmin_person_index', 'attributes' => ['class' => 'menu_people']];
            $tables->addChild('People', $options);
        }
    }

    /**
     * @param KnpItemInterface $tables
     */
    protected function addTagsMenu(KnpItemInterface $tables)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_TAGS)) {
            $options = ['route' => 'pumukitnewadmin_tag_index', 'attributes' => ['class' => 'menu_tags']];
            $tables->addChild('Tags', $options);
        }
    }

    /**
     * @param KnpItemInterface $tables
     */
    protected function addPlaceAndPrecinctMenu(KnpItemInterface $tables)
    {
        $menuPlaceAndPrecinct = $this->container->hasParameter('pumukit_new_admin.show_menu_place_and_precinct') ? $this->container->getParameter('pumukit_new_admin.show_menu_place_and_precinct') : false;
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_TAGS) && $menuPlaceAndPrecinct) {
            $options = ['route' => 'pumukitnewadmin_places_index', 'attributes' => ['class' => 'menu_places_and_precinct']];
            $tables->addChild('Places and precinct', $options);
        }
    }

    /**
     * @param KnpItemInterface $tables
     */
    protected function addSeriesTypeMenu(KnpItemInterface $tables)
    {
        $showSeriesTypeTab = $this->container->hasParameter('pumukit.use_series_channels') && $this->container->getParameter('pumukit.use_series_channels');
        if ($showSeriesTypeTab && $this->authorizationChecker->isGranted(Permission::ACCESS_SERIES_TYPES)) {
            $options = ['route' => 'pumukitnewadmin_seriestype_index', 'attributes' => ['class' => 'menu_series_type']];
            $tables->addChild('Series types', $options);
        }
    }

    /**
     * @param KnpItemInterface $menu
     */
    protected function addManagementMenu(KnpItemInterface $menu)
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
     * @param KnpItemInterface $management
     */
    protected function addAdminUsersMenu(KnpItemInterface $management)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_ADMIN_USERS)) {
            $options = ['route' => 'pumukitnewadmin_user_index', 'attributes' => ['class' => 'menu_users']];
            $management->addChild('Admin users', $options);
        }
    }

    /**
     * @param KnpItemInterface $management
     */
    protected function addGroupsMenu(KnpItemInterface $management)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_GROUPS)) {
            $options = ['route' => 'pumukitnewadmin_group_index', 'attributes' => ['class' => 'menu_groups']];
            $management->addChild('Groups', $options);
        }
    }

    /**
     * @param KnpItemInterface $management
     */
    protected function addPermissionProfilesMenu(KnpItemInterface $management)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_PERMISSION_PROFILES)) {
            $options = ['route' => 'pumukitnewadmin_permissionprofile_index', 'attributes' => ['class' => 'menu_permission_profiles']];
            $management->addChild('Permission profiles', $options);
        }
    }

    /**
     * @param KnpItemInterface $management
     */
    protected function addRolesMenu(KnpItemInterface $management)
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_ROLES)) {
            $options = ['route' => 'pumukitnewadmin_role_index', 'attributes' => ['class' => 'menu_roles']];
            $management->addChild('Roles', $options);
        }
    }

    /**
     * @param KnpItemInterface $menu
     */
    protected function addToolsMenu(KnpItemInterface $menu)
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
     * @param KnpItemInterface      $menu
     * @param ItemInterface         $item
     * @param null|KnpItemInterface $tools
     */
    protected function addDynamicToolMenu(KnpItemInterface $menu, ItemInterface $item, $tools)
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
     * @param KnpItemInterface $menu
     */
    protected function addCustomMenu(KnpItemInterface $menu)
    {
        // NOTE: Override this function to add new item menu in PuMuKIT
    }
}
