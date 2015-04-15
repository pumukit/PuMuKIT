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

        // Add translations in src/Pumukit/NewAdminBundle/Resource/translations/NewAdminBundle.locale.yml
        $menu->addChild('Dashboard', array('route' => 'pumukit_newadmin_dashboard_index'))->setExtra('translation_domain', 'NewAdminBundle');

        $series = $menu->addChild('Multimedia Series', array('route' => 'pumukitnewadmin_series_index'))->setExtra('translation_domain', 'NewAdminBundle');
        $series->addChild('Multimedia', array('route' => 'pumukitadmin_mms_index'))->setExtra('translation_domain', 'NewAdminBundle');
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
                
        $live = $menu->addChild('Live')->setExtra('translation_domain', 'NewAdminBundle');
        $live->addChild('Live Channels', array('route' => 'pumukitnewadmin_live_index'))->setExtra('translation_domain', 'NewAdminBundle');
        $live->addChild('Live Events', array('route' => 'pumukitnewadmin_event_index'))->setExtra('translation_domain', 'NewAdminBundle');

        //$menu->addChild('Temporized Publishing');

        $tables = $menu->addChild('Tables')->setExtra('translation_domain', 'NewAdminBundle');
        $tables->addChild('People', array('route' => 'pumukitnewadmin_person_index'))->setExtra('translation_domain', 'NewAdminBundle');

        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
          $management = $menu->addChild('Management')->setExtra('translation_domain', 'NewAdminBundle');
          $management->addChild('Admin users', array('route' => 'pumukitnewadmin_user_index'))->setExtra('translation_domain', 'NewAdminBundle');
          $management->addChild('Tags', array('route' => 'pumukitnewadmin_tag_index'))->setExtra('translation_domain', 'NewAdminBundle');
          $management->addChild('Roles', array('route' => 'pumukitnewadmin_role_index'))->setExtra('translation_domain', 'NewAdminBundle');
          $management->addChild('Access Profiles', array('route' => 'pumukitnewadmin_broadcast_index'))->setExtra('translation_domain', 'NewAdminBundle');
        }

        $ingester = $menu->addChild('Ingester')->setExtra('translation_domain', 'NewAdminBundle');
        $ingester->addChild('Matterhorn Ingester')->setExtra('translation_domain', 'NewAdminBundle');

        return $menu;
    }
}
