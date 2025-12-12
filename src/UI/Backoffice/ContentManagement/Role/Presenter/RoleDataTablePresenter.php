<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Role\Presenter;

use App\UI\Backoffice\Shared\Helpers\BooleanIcon;
use Pumukit\SchemaBundle\Document\Role;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class RoleDataTablePresenter
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly Environment $twig,
    ) {}

    public function present(Role $role): array
    {
        return [
            'id' => $role->getId(),
            'cod' => $role->getCod(),
            'name' => $role->getName(),
            'text' => $role->getText(),
            'display' => BooleanIcon::convert($role->getDisplay()),
            'readOnly' => BooleanIcon::convert($role->getReadOnly()),
            'rank' => $role->getRank(),
            'number_people' => $role->getNumberPeopleInMultimediaObject(),
            'actions' => $this->renderActions($role),
        ];
    }

    private function renderActions(Role $role): string
    {
        $viewButton = $this->twig->render('@Shared/Views/components/table/buttons/_view_button.html.twig', [
            'url' => $this->router->generate('role_view', ['id' => $role->getId()]),
        ]);

        $editButton = $this->twig->render('@Shared/Views/components/table/buttons/_edit_button.html.twig', [
            'url' => $this->router->generate('role_update', ['id' => $role->getId()]),
        ]);

        $deleteButton = $this->twig->render('@Shared/Views/components/table/buttons/_delete_button.html.twig', [
            'url' => $this->router->generate('role_delete', ['id' => $role->getId()]),
            'confirm' => 'Are you sure you want to delete this role?',
        ]);

        return $viewButton.$editButton.$deleteButton;
    }
}
