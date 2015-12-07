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
        $authorizationChecker = $this->container->get('security.authorization_checker');
        if ($authorizationChecker->isGranted('ROLE_ADMIN')) {
            $menu->addChild('Dashboard', array('route' => 'pumukit_newadmin_dashboard_index'))->setExtra('translation_domain', 'NewAdminBundle');
        }

        $series = $menu->addChild('Multimedia Series', array('route' => 'pumukitnewadmin_series_index'))->setExtra('translation_domain', 'NewAdminBundle');
        $series->addChild('Multimedia', array('route' => 'pumukitnewadmin_mms_index'))->setExtra('translation_domain', 'NewAdminBundle');
        $series->setDisplayChildren(false);

        if ($authorizationChecker->isGranted('ROLE_ADMIN')) {
            $live = $menu->addChild('Live')->setExtra('translation_domain', 'NewAdminBundle');
            $live->addChild('Live Channels', array('route' => 'pumukitnewadmin_live_index'))->setExtra('translation_domain', 'NewAdminBundle');
            $live->addChild('Live Events', array('route' => 'pumukitnewadmin_event_index'))->setExtra('translation_domain', 'NewAdminBundle');

            $menu->addChild('Encoder jobs', array('route' => 'pumukit_encoder_info'))->setExtra('translation_domain', 'NewAdminBundle');        

            $tables = $menu->addChild('Tables')->setExtra('translation_domain', 'NewAdminBundle');
            $tables->addChild('People', array('route' => 'pumukitnewadmin_person_index'))->setExtra('translation_domain', 'NewAdminBundle');
            $tables->addChild('Tags', array('route' => 'pumukitnewadmin_tag_index'))->setExtra('translation_domain', 'NewAdminBundle');
            $tables->addChild('Access profiles', array('route' => 'pumukitnewadmin_broadcast_index'))->setExtra('translation_domain', 'NewAdminBundle');
            $tables->addChild('Series types', array('route' => 'pumukitnewadmin_seriestype_index'))->setExtra('translation_domain', 'NewAdminBundle');
        }

        if ($authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
          $management = $menu->addChild('Management')->setExtra('translation_domain', 'NewAdminBundle');
          $management->addChild('Admin users', array('route' => 'pumukitnewadmin_user_index'))->setExtra('translation_domain', 'NewAdminBundle');
          $management->addChild('Permission profiles', array('route' => 'pumukitnewadmin_permissionprofile_index'))->setExtra('translation_domain', 'NewAdminBundle');
          $management->addChild('Roles', array('route' => 'pumukitnewadmin_role_index'))->setExtra('translation_domain', 'NewAdminBundle');
        }

        if ($authorizationChecker->isGranted('ROLE_ADMIN')) {
            if ($this->container->has("pumukit_opencast.client")) {
                $ingester = $menu->addChild('Ingester')->setExtra('translation_domain', 'NewAdminBundle');
                $ingester->addChild('Opencast Ingester', array('route' => 'pumukitopencast'))->setExtra('translation_domain', 'NewAdminBundle');
            }
        }

        return $menu;
    }
}
