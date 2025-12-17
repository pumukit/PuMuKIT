<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Presenter;

use App\IdentityAndAccess\Group\Domain\ValueObject\GroupUserListItem;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class GroupUserDataTablePresenter
{
    public function __construct(
        private RouterInterface $router,
        private Environment $twig,
    ) {}

    public function present(GroupUserListItem $user, string $groupId): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'full_name' => $user->fullName ?? 'N/A',
            'email' => $user->email ?? 'N/A',
            'actions' => $this->renderActions($user, $groupId),
        ];
    }

    private function renderActions(GroupUserListItem $user, string $groupId): string
    {
        $viewButton = $this->twig->render('@Shared/Views/components/table/buttons/_view_button.html.twig', [
            'url' => $this->router->generate('user_view', ['id' => $user->id]),
        ]);

        $removeButton = $this->twig->render('@Shared/Views/components/table/buttons/_delete_button.html.twig', [
            'url' => '#',
            'confirm' => 'Are you sure you want to remove this user from the group?',
            'label' => 'Delete',
            'icon' => 'fa-user-times',
            'class' => 'btn btn-danger btn-sm remove-user-btn',
            'data' => [
                'user-id' => $user->id,
                'user-name' => $user->username,
                'group-id' => $groupId,
            ],
        ]);

        return $viewButton.$removeButton;
    }
}
