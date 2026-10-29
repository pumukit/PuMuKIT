<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(Permission::ACCESS_DASHBOARD)]
class DashboardController extends AbstractController implements NewAdminControllerInterface
{
    #[Route('/dashboard', name: 'pumukit_newadmin_dashboard_index')]
    #[Route('/dashboard/default', name: 'pumukit_newadmin_dashboard_index_default')]
    #[Template('@PumukitNewAdmin/Dashboard/index.html.twig')]
    public function indexAction(): array
    {
        $isGlobal = $this->isGranted(PermissionProfile::SCOPE_GLOBAL) || $this->isGranted('ROLE_SUPER_ADMIN');

        return [
            'is_global' => $isGlobal,
            'show_jobs' => $isGlobal && $this->isGranted(Permission::ACCESS_JOBS),
            'show_views' => $this->isGranted('ROLE_ACCESS_STATS'),
        ];
    }
}
