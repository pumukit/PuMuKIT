<?php

namespace Pumukit\AdminBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class Builder extends ContainerAware
{
    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        $menu->addChild('Dashboard', array('route' => 'pumukit_admin_dashboard_index'));

        $series = $menu->addChild('Multimedia Series', array('route' => 'pumukitadmin_series_index'));
        $series->addChild('Multimedia', array('route' => 'pumukitadmin_mms_index'));
        $series->setDisplayChildren(false);

        $menu->addChild('Unesco Cataloger');

        $portal_design = $menu->addChild('WebTV Portal Design');
        $portal_design->addChild('Design');
        $portal_design->addChild('Templates');
        $portal_design->addChild('FileManager');
        $portal_design->addChild('Tags');
        $portal_design->addChild('News');

        $live = $menu->addChild('Live');
        $live->addChild('Live Channels', array('route' => 'pumukitadmin_direct_index'));
        $live->addChild('Live Events', array('route' => 'pumukitadmin_event_index'));

        $transcoding = $menu->addChild('Transcoding');
        $transcoding->addChild('Transcoder Profile');
        $transcoding->addChild('Task list');
        $transcoding->addChild('Transcoders');

        $menu->addChild('Temporized Publishing');

        $tables = $menu->addChild('Tables');
        $tables->addChild('People', array('route' => 'pumukitadmin_person_index'));

        $management = $menu->addChild('Management');
        $management->addChild('Admin users', array('route' => 'pumukitadmin_user_index'));
        $management->addChild('Tags', array('route' => 'pumukitadmin_tag_index'));
        $management->addChild('Genres');
        $management->addChild('Material types');
        $management->addChild('Series types');
        $management->addChild('Languages');
        $management->addChild('Roles', array('route' => 'pumukitadmin_role_index'));
        $management->addChild('Access Profiles', array('route' => 'pumukitadmin_broadcast_index'));
        $management->addChild('Broadcast Servers');

        $ingester = $menu->addChild('Ingester');
        $ingester->addChild('Matterhorn Ingester');

        return $menu;
    }
}
