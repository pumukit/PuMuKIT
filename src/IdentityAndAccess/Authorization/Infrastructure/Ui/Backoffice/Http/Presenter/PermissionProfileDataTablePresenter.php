<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Infrastructure\Ui\Backoffice\Http\Presenter;

use App\Shared\Infrastructure\Ui\Backoffice\Http\Helpers\BooleanIcon;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class PermissionProfileDataTablePresenter
{
    public function __construct(
        private RouterInterface $router,
        private Environment $twig,
    ) {}

    public function present(PermissionProfile $permissionProfile): array
    {
        return [
            'id' => $permissionProfile->getId(),
            'name' => $permissionProfile->getName(),
            'scope' => $this->getScopeLabel($permissionProfile->getScope()),
            'permissions_count' => count($permissionProfile->getPermissions()),
            'system' => BooleanIcon::convert($permissionProfile->isSystem()),
            'default' => BooleanIcon::convert($permissionProfile->isDefault()),
            'actions' => $this->renderActions($permissionProfile),
        ];
    }

    private function renderActions(PermissionProfile $permissionProfile): string
    {
        $viewButton = $this->twig->render('@Shared/Views/components/table/buttons/_view_button.html.twig', [
            'url' => $this->router->generate('permission_profile_view', ['id' => $permissionProfile->getId()]),
        ]);

        $editButton = $this->twig->render('@Shared/Views/components/table/buttons/_edit_button.html.twig', [
            'url' => $this->router->generate('permission_profile_update', ['id' => $permissionProfile->getId()]),
        ]);

        $deleteButton = '';
        if (!$permissionProfile->isSystem()) {
            $deleteButton = $this->twig->render('@Shared/Views/components/table/buttons/_delete_button.html.twig', [
                'url' => $this->router->generate('permission_profile_delete', ['id' => $permissionProfile->getId()]),
                'confirm' => 'Are you sure you want to delete this permission profile?',
            ]);
        }

        return $viewButton.$editButton.$deleteButton;
    }

    private function getScopeLabel(string $scope): string
    {
        return match ($scope) {
            PermissionProfile::SCOPE_GLOBAL => 'Global',
            PermissionProfile::SCOPE_PERSONAL => 'Personal',
            PermissionProfile::SCOPE_NONE => 'None',
            default => $scope,
        };
    }
}

