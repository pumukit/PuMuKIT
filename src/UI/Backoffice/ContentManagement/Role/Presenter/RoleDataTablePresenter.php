<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Role\Presenter;

use App\UI\Backoffice\ContentManagement\MultimediaObject\Helpers\DurationFormat;
use App\UI\Backoffice\ContentManagement\MultimediaObject\Helpers\StatusIcon;
use App\UI\Backoffice\ContentManagement\MultimediaObject\Helpers\TypeIcon;
use App\UI\Backoffice\Shared\Helpers\BooleanIcon;
use App\UI\Backoffice\Shared\Helpers\DateFormat;
use App\UI\Backoffice\Shared\Helpers\TextTruncate;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final readonly class RoleDataTablePresenter
{
    public function __construct(
        private RouterInterface $router,
        private Environment $twig,
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
        return $this->twig->render('@Shared/Views/components/table/_datatable_actions.html.twig', [
            'actions' => [
                [
                    'type' => 'link',
                    'url' => $this->router->generate('role_create', ['id' => $role->getId()]),
                    'style' => 'info',
                    'icon' => 'eye',
                    'title' => 'View',
                ],
                [
                    'type' => 'link',
                    'url' => $this->router->generate('role_update', ['id' => $role->getId()]),
                    'style' => 'warning',
                    'icon' => 'edit',
                    'title' => 'Edit'
                ],
                [
                    'type' => 'form',
                    'url' => $this->router->generate('role_delete', ['id' => $role->getId()]),
                    'style' => 'danger',
                    'icon' => 'trash',
                    'title' => 'Delete',
                    'confirm' => 'Are you sure you want to delete this role?',
                ],
            ],
        ]);
    }
}
