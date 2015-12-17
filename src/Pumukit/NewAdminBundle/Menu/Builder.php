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
        $menu->setAttribute('class', 'nav navbar-nav');

        // Add translations in src/Pumukit/NewAdminBundle/Resource/translations/NewAdminBundle.locale.yml
        $authorizationChecker = $this->container->get('security.authorization_checker');
        $createBroadcastDisabled = $this->container->getParameter('pumukitschema.disable_broadcast_creation');
        if (false !== $authorizationChecker->isGranted(Permission::ACCESS_DASHBOARD)) {
            $menu->addChild('Dashboard', array('route' => 'pumukit_newadmin_dashboard_index'))->setExtra('translation_domain', 'NewAdminBundle');
        }

        if ($authorizationChecker->isGranted(Permission::ACCESS_MULTIMEDIA_SERIES)) {
            $series = $menu->addChild('Multimedia Series', array('route' => 'pumukitnewadmin_series_index'))->setExtra('translation_domain', 'NewAdminBundle');
            $series->addChild('Multimedia', array('route' => 'pumukitnewadmin_mms_index'))->setExtra('translation_domain', 'NewAdminBundle');
            $series->setDisplayChildren(false);
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
            $authorizationChecker->isGranted(Permission::ACCESS_BROADCASTS) || $authorizationChecker->isGranted(Permission::ACCESS_SERIES_TYPES)) {
            $tables = $menu->addChild('Tables')->setExtra('translation_domain', 'NewAdminBundle');
            if ($authorizationChecker->isGranted(Permission::ACCESS_PEOPLE)) {
                $tables->addChild('People', array('route' => 'pumukitnewadmin_person_index'))->setExtra('translation_domain', 'NewAdminBundle');
            }
            if ($authorizationChecker->isGranted(Permission::ACCESS_TAGS)) {
                $tables->addChild('Tags', array('route' => 'pumukitnewadmin_tag_index'))->setExtra('translation_domain', 'NewAdminBundle');
            }
            if ($authorizationChecker->isGranted(Permission::ACCESS_BROADCASTS) && !$createBroadcastDisabled) {
                $tables->addChild('Access profiles', array('route' => 'pumukitnewadmin_broadcast_index'))->setExtra('translation_domain', 'NewAdminBundle');
            }
            if ($authorizationChecker->isGranted(Permission::ACCESS_SERIES_TYPES)) {
                $tables->addChild('Series types', array('route' => 'pumukitnewadmin_seriestype_index'))->setExtra('translation_domain', 'NewAdminBundle');
            }
        }

        if ($authorizationChecker->isGranted(Permission::ACCESS_ADMIN_USERS) || $authorizationChecker->isGranted(Permission::ACCESS_PERMISSION_PROFILES) ||
            $authorizationChecker->isGranted(Permission::ACCESS_ROLES)) {
            $management = $menu->addChild('Management')->setExtra('translation_domain', 'NewAdminBundle');
            if ($authorizationChecker->isGranted(Permission::ACCESS_ADMIN_USERS)) {
                $management->addChild('Admin users', array('route' => 'pumukitnewadmin_user_index'))->setExtra('translation_domain', 'NewAdminBundle');
            }
            if ($authorizationChecker->isGranted(Permission::ACCESS_PERMISSION_PROFILES)) {
                $management->addChild('Permission profiles', array('route' => 'pumukitnewadmin_permissionprofile_index'))->setExtra('translation_domain', 'NewAdminBundle');
            }
            if ($authorizationChecker->isGranted(Permission::ACCESS_ROLES)) {
                $management->addChild('Roles', array('route' => 'pumukitnewadmin_role_index'))->setExtra('translation_domain', 'NewAdminBundle');
            }
        }

        if ($authorizationChecker->isGranted(Permission::ACCESS_INGESTOR)) {
            if ($this->container->has("pumukit_opencast.client")) {
                $ingester = $menu->addChild('Ingester')->setExtra('translation_domain', 'NewAdminBundle');
                $ingester->addChild('Opencast Ingester', array('route' => 'pumukitopencast'))->setExtra('translation_domain', 'NewAdminBundle');
            }
        }

        return $menu;
    }
}
