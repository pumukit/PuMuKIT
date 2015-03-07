<?php

namespace Pumukit\NewAdminBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class Builder extends ContainerAware
{
    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');
        $menu->setAttribute('class', 'nav navbar-nav');

        $menu->addChild('Dashboard', array('route' => 'pumukit_newadmin_dashboard_index'));

        $series = $menu->addChild('Multimedia Series', array('route' => 'pumukitnewadmin_series_index'));
        $series->addChild('Multimedia', array('route' => 'pumukitadmin_mms_index'));
        $series->setDisplayChildren(false);

        
        //$menu->addChild('Unesco Cataloger');

        /*
        $portal_design = $menu->addChild('WebTV Portal Design');
        $portal_design->addChild('Design');
        $portal_design->addChild('Templates');
        $portal_design->addChild('FileManager');
        $portal_design->addChild('Tags');
        $portal_design->addChild('News');
        */
                
        $live = $menu->addChild('Live');
        $live->addChild('Live Channels', array('route' => 'pumukitnewadmin_live_index'));
        $live->addChild('Live Events', array('route' => 'pumukitnewadmin_event_index'));

        //$menu->addChild('Temporized Publishing');

        $tables = $menu->addChild('Tables');
        $tables->addChild('People', array('route' => 'pumukitnewadmin_person_index'));

        $management = $menu->addChild('Management');
        $management->addChild('Admin users', array('route' => 'pumukitnewadmin_user_index'));
        $management->addChild('Tags', array('route' => 'pumukitnewadmin_tag_index'));
        $management->addChild('Genres');
        $management->addChild('Material types');
        $management->addChild('Series types');
        $management->addChild('Languages');
        $management->addChild('Roles', array('route' => 'pumukitnewadmin_role_index'));
        $management->addChild('Access Profiles', array('route' => 'pumukitadmin_broadcast_index'));
        $management->addChild('Broadcast Servers');

        $ingester = $menu->addChild('Ingester');
        $ingester->addChild('Matterhorn Ingester');

        return $menu;
    }
}
