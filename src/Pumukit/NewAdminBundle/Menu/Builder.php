<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface as KnpItemInterface;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private $authorizationChecker;

    public function mainMenu(FactoryInterface $factory, array $options): KnpItemInterface
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
        $this->addUserMenu($menu);
        $this->addCustomMenu($menu);

        return $menu;
    }

    protected function addDashboardMenu(KnpItemInterface $menu): void
    {
        $showDashboardTab = $this->container->getParameter('pumukit.show_dashboard_tab');

        if ($showDashboardTab && $this->authorizationChecker->isGranted(Permission::ACCESS_DASHBOARD)) {
            $options = ['route' => 'pumukit_newadmin_dashboard_index', 'attributes' => ['class' => 'menu_dashboard']];
            $menu->addChild('Dashboard', $options);
        }
    }

    protected function addWizardMenu(KnpItemInterface $menu): void
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

    protected function addMediaManagerMenu(KnpItemInterface $menu): void
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_MULTIMEDIA_SERIES) || $this->authorizationChecker->isGranted(Permission::ACCESS_EDIT_PLAYLIST)) {
            $options = ['attributes' => ['class' => 'menu_media_manager']];
            $mediaManager = $menu->addChild('Media Manager', $options);
            $this->addMediaManagerChildrenMenu($mediaManager);
        }
    }

    protected function addMediaManagerChildrenMenu(KnpItemInterface $mediaManager): void
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

        if ($this->authorizationChecker->isGranted(Permission::ACCESS_EDIT_PLAYLIST) && $this->container->getParameter('pumukit_new_admin.enable_playlist')) {
            $options = ['route' => 'pumukitnewadmin_playlist_index', 'attributes' => ['class' => 'menu_playlists_index']];
            $mediaManager->addChild('Moodle Playlists', $options);
        }
    }

    protected function addStatsMenu(KnpItemInterface $menu): void
    {
        if ($this->authorizationChecker->isGranted('ROLE_ACCESS_STATS')) {
            $options = ['attributes' => ['class' => 'menu_stats']];
            $stats = $menu->addChild('Stats', $options);

            $options = ['route' => 'pumukit_stats_series_index', 'attributes' => ['class' => 'menu_stats_series']];
            $stats->addChild('Series', $options);

            $options = ['route' => 'pumukit_stats_mmobj_index', 'attributes' => ['class' => 'menu_stats_multimedia_object']];
            $stats->addChild('Multimedia Objects', $options);

            // Voters are a way to check if a menu item is the current one. Now we are just checking the routes and setting the Current element manually
            $route = $this->container->get('request_stack')->getMasterRequest()->attributes->get('_route');
            $statsRoutes = ['pumukit_stats_series_index', 'pumukit_stats_mmobj_index', 'pumukit_stats_series_index_id', 'pumukit_stats_mmobj_index_id'];
            if (in_array($route, $statsRoutes)) {
                $stats->setCurrent(true);
            }
        }
    }

    protected function addLiveMenu(KnpItemInterface $menu): void
    {
        $advanceLiveEvent = $this->container->hasParameter('pumukit_new_admin.advance_live_event')
                            && $this->container->getParameter('pumukit_new_admin.advance_live_event');
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

    protected function addAdvancedLive(KnpItemInterface $live): void
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

    protected function addBasicLive(KnpItemInterface $live): void
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

    protected function addJobMenu(KnpItemInterface $menu): void
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_JOBS)) {
            $options = ['route' => 'pumukit_encoder_info', 'attributes' => ['class' => 'menu_encoder']];
            $menu->addChild('Encoder jobs', $options);
        }
    }

    protected function addTablesMenu(KnpItemInterface $menu): void
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_TAGS)
            || $this->authorizationChecker->isGranted(Permission::ACCESS_SERIES_TYPES)
            || ($this->authorizationChecker->isGranted(Permission::ACCESS_PEOPLE) && $this->authorizationChecker->isGranted(Permission::SHOW_PEOPLE_MENU))
        ) {
            $options = ['attributes' => ['class' => 'menu_tables']];
            $tables = $menu->addChild('Tables', $options);

            $this->addPeopleMenu($tables);
            $this->addTagsMenu($tables);
            $this->addPlaceAndPrecinctMenu($tables);
            $this->addSeriesTypeMenu($tables);
        }
    }

    protected function addPeopleMenu(KnpItemInterface $tables): void
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_PEOPLE) && $this->authorizationChecker->isGranted(Permission::SHOW_PEOPLE_MENU)) {
            $options = ['route' => 'pumukitnewadmin_person_index', 'attributes' => ['class' => 'menu_people']];
            $tables->addChild('People', $options);
        }
    }

    protected function addTagsMenu(KnpItemInterface $tables): void
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_TAGS)) {
            $options = ['route' => 'pumukitnewadmin_tag_index', 'attributes' => ['class' => 'menu_tags']];
            $tables->addChild('Tags', $options);
        }
    }

    protected function addPlaceAndPrecinctMenu(KnpItemInterface $tables): void
    {
        $menuPlaceAndPrecinct = $this->container->hasParameter('pumukit_new_admin.show_menu_place_and_precinct')
                                && $this->container->getParameter('pumukit_new_admin.show_menu_place_and_precinct');
        if ($menuPlaceAndPrecinct && $this->authorizationChecker->isGranted(Permission::ACCESS_TAGS)) {
            $options = ['route' => 'pumukitnewadmin_places_index', 'attributes' => ['class' => 'menu_places_and_precinct']];
            $tables->addChild('Places and precinct', $options);
        }
    }

    protected function addSeriesTypeMenu(KnpItemInterface $tables): void
    {
        $showSeriesTypeTab = $this->container->hasParameter('pumukit.use_series_channels') && $this->container->getParameter('pumukit.use_series_channels');
        if ($showSeriesTypeTab && $this->authorizationChecker->isGranted(Permission::ACCESS_SERIES_TYPES)) {
            $options = ['route' => 'pumukitnewadmin_seriestype_index', 'attributes' => ['class' => 'menu_series_type']];
            $tables->addChild('Series types', $options);
        }
    }

    protected function addManagementMenu(KnpItemInterface $menu): void
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

    protected function addAdminUsersMenu(KnpItemInterface $management): void
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_ADMIN_USERS)) {
            $options = ['route' => 'pumukitnewadmin_user_index', 'attributes' => ['class' => 'menu_users']];
            $management->addChild('Admin users', $options);
        }
    }

    protected function addGroupsMenu(KnpItemInterface $management): void
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_GROUPS)) {
            $options = ['route' => 'pumukitnewadmin_group_index', 'attributes' => ['class' => 'menu_groups']];
            $management->addChild('Groups', $options);
        }
    }

    protected function addPermissionProfilesMenu(KnpItemInterface $management): void
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_PERMISSION_PROFILES)) {
            $options = ['route' => 'pumukitnewadmin_permissionprofile_index', 'attributes' => ['class' => 'menu_permission_profiles']];
            $management->addChild('Permission profiles', $options);
        }
    }

    protected function addRolesMenu(KnpItemInterface $management): void
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_ROLES)) {
            $options = ['route' => 'pumukitnewadmin_role_index', 'attributes' => ['class' => 'menu_roles']];
            $management->addChild('Roles', $options);
        }
    }

    protected function addToolsMenu(KnpItemInterface $menu): void
    {
        $showImporterTab = $this->container->hasParameter('pumukit_opencast.show_importer_tab') && $this->container->getParameter('pumukit_opencast.show_importer_tab');
        $hasAccessToImporter = $showImporterTab && $this->authorizationChecker->isGranted('ROLE_ACCESS_IMPORTER');
        $hasAccessToSeriesStyle = $this->authorizationChecker->isGranted(Permission::ACCESS_SERIES_STYLE);
        $hasAccessToHeadAndTailManager = $this->authorizationChecker->isGranted(Permission::ACCESS_HEAD_AND_TAIL_MANAGER);

        $externalTools = [];

        $menuItems = $this->container->get('pumukitnewadmin.menu');
        if (!$menuItems) {
            return;
        }

        foreach ($menuItems->items() as $item) {
            if ('menu' !== $item->getServiceTag() || !$this->authorizationChecker->isGranted($item->getAccessRole())) {
                continue;
            }
            $externalTools[] = $item;
        }

        $hasAccessToAnyExternalTool = count($externalTools) > 0;
        $hasAccessToAnyTool = $hasAccessToImporter || $hasAccessToSeriesStyle || $hasAccessToAnyExternalTool || $hasAccessToHeadAndTailManager;

        if (!$hasAccessToAnyTool) {
            return;
        }

        $options = ['attributes' => ['class' => 'menu_tools']];
        $root = $menu->addChild('Tools', $options);

        if ($hasAccessToImporter) {
            $options = ['route' => 'pumukitopencast', 'attributes' => ['class' => 'menu_tools_opencast']];
            $root->addChild('OC-Importer', $options);
        }

        if ($hasAccessToSeriesStyle) {
            $options = ['route' => 'pumukit_newadmin_series_styles', 'attributes' => ['class' => 'menu_series_styles']];
            $root->addChild('Series style', $options);
        }

        if ($hasAccessToHeadAndTailManager) {
            $options = ['route' => 'pumukit_newadmin_head_and_tail', 'attributes' => ['class' => 'menu_head_and_tail']];
            $root->addChild('Head & tail manager', $options);
        }

        foreach ($externalTools as $item) {
            $this->addDynamicToolMenu($item, $root);
        }
    }

    protected function addDynamicToolMenu(ItemInterface $item, KnpItemInterface $root): void
    {
        $class = 'menu_tools_'.strtolower(str_replace(' ', '_', $item->getName()));
        $options = ['route' => $item->getUri(), 'attributes' => ['class' => $class]];
        $root->addChild($item->getName(), $options);
    }

    protected function addUserMenu(KnpItemInterface $management): void
    {
        if (!$this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
            $options = ['route' => 'pumukitnewadmin_profile_user_index', 'attributes' => ['class' => 'menu_user']];
            $management->addChild('My profile', $options);
        }
    }

    protected function addCustomMenu(KnpItemInterface $menu): void
    {
        $blockItems = $this->container->get('pumukitnewadmin.block');
        if (!$blockItems) {
            return;
        }

        foreach ($blockItems->items() as $item) {
            $class = 'menu_tools_'.strtolower(str_replace(' ', '_', $item->getName()));
            $options = ['route' => $item->getUri(), 'attributes' => ['class' => $class]];
            $menu->addChild($item->getName(), $options);
        }
    }
}
