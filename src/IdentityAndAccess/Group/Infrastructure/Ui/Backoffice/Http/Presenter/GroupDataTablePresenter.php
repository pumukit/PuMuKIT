<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Presenter;

use App\Shared\Infrastructure\Ui\Backoffice\Http\Helpers\DateFormat;
use App\Shared\Infrastructure\Ui\Backoffice\Http\Helpers\TextTruncate;
use Pumukit\SchemaBundle\Document\Group;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class GroupDataTablePresenter
{
    public function __construct(
        private RouterInterface $router,
        private Environment $twig,
    ) {}

    public function present(Group $group): array
    {
        return [
            'id' => $group->getId(),
            'key' => $group->getKey(),
            'name' => $group->getName(),
            'origin' => $group->getOrigin(),
            'comments' => TextTruncate::medium($group->getComments() ?? ''),
            'created_at' => DateFormat::format($group->getCreatedAt()),
            'updated_at' => DateFormat::format($group->getUpdatedAt()),
            'actions' => $this->renderActions($group),
        ];
    }

    private function renderActions(Group $group): string
    {
        $viewButton = $this->twig->render('@Shared/Views/components/table/buttons/_view_button.html.twig', [
            'url' => $this->router->generate('group_view', ['id' => $group->getId()]),
        ]);

        $editButton = $this->twig->render('@Shared/Views/components/table/buttons/_edit_button.html.twig', [
            'url' => $this->router->generate('group_update', ['id' => $group->getId()]),
        ]);

        $deleteButton = $this->twig->render('@Shared/Views/components/table/buttons/_delete_button.html.twig', [
            'url' => $this->router->generate('group_delete', ['id' => $group->getId()]),
            'confirm' => 'group.delete.confirm',
        ]);

        return $viewButton.$editButton.$deleteButton;
    }
}
