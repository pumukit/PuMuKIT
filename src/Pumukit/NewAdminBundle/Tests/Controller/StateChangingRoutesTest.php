<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 */
#[CoversNothing]
class StateChangingRoutesTest extends KernelTestCase
{
    #[DataProvider('stateChangingRoutes')]
    public function testTheRouteDoesNotAcceptGet(string $name): void
    {
        $route = static::getContainer()->get('router')->getRouteCollection()->get($name);

        $this->assertNotNull($route, "The route {$name} no longer exists.");
        $this->assertNotSame([], $route->getMethods(), "{$name} accepts any method.");
        $this->assertNotContains('GET', $route->getMethods());
    }

    public static function stateChangingRoutes(): iterable
    {
        $routes = [
            'pumukitnewadmin_mms_up',
            'pumukitnewadmin_mms_down',
            'pumukitnewadmin_mms_top',
            'pumukitnewadmin_mms_bottom',
            'pumukitnewadmin_role_up',
            'pumukitnewadmin_role_down',
            'pumukitnewadmin_role_top',
            'pumukitnewadmin_role_bottom',
            'pumukitnewadmin_person_up',
            'pumukitnewadmin_person_down',
            'pumukitnewadmin_person_link',
            'pumukitnewadmin_playlistmms_up',
            'pumukitnewadmin_playlistmms_down',
            'pumukitnewadmin_playlistmms_top',
            'pumukitnewadmin_playlistmms_bottom',
            'pumukitnewadmin_playlistmms_batch_add',
            'pumukitnewadmin_playlistmms_batch_delete',
            'pumukitnewadmin_mms_invert_announce',
            'pumukitnewadmin_series_invert_announce',
            'pumukitnewadmin_series_create',
            'pumukitnewadmin_mms_create',
            'pumukit_newadmin_series_styles_create',
            'pumukit_newadmin_series_styles_edit',
            'pumukit_newadmin_series_styles_delete',
            'pumukitnewadmin_places_delete',
            'pumukitnewadmin_track_pic',
            'pumukitnewadmin_track_retranscode',
            'pumukitnewadmin_unesco_add_tag',
            'pumukitnewadmin_unesco_clone',
            'pumukitnewadmin_unesco_delete',
            'pumukitnewadmin_unesco_delete_tag',
            'pumukitnewadmin_unesco_options_list',
            'pumukit_newadmin_head_and_tail_manager_remove_item',
            'pumukit_new_admin_live_event_create',
            'pumukit_new_admin_live_event_clone_session',
            'pumukit_new_admin_live_event_delete_selected',
            'pumukit_new_admin_live_event_session_delete',
            'pumukitnewadmin_live_event_edit_series',
            'pumukit_new_admin_live_event_options',
            'pumukit_new_admin_autocomplete_series_with_event_data',
            'pumukit_encoder_cpu_maintenance_switch',
            'wizard_upload_from_server',
            'inbox_auto_import',
        ];

        foreach ($routes as $route) {
            yield $route => [$route];
        }
    }
}
