<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Infrastructure\Ui\Backoffice\Http\Presenter;

use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class GroupUserDataTablePresenter
{
    public function __construct(
        private RouterInterface $router,
        private Environment $twig,
    ) {}

    public function present(User $user, string $groupId): array
    {
        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'full_name' => $user->getFullName() ?? 'N/A',
            'email' => $user->getEmail() ?? 'N/A',
            'actions' => $this->renderActions($user, $groupId),
        ];
    }

    private function renderActions(User $user, string $groupId): string
    {
        $viewButton = $this->twig->render('@Shared/Views/components/table/buttons/_view_button.html.twig', [
            'url' => $this->router->generate('user_view', ['id' => $user->getId()]),
        ]);

        $removeButton = $this->twig->render('@Shared/Views/components/table/buttons/_delete_button.html.twig', [
            'url' => '#',
            'confirm' => 'Are you sure you want to remove this user from the group?',
            'label' => 'Delete',
            'icon' => 'fa-user-times',
            'class' => 'btn btn-danger btn-sm remove-user-btn',
            'data' => [
                'user-id' => $user->getId(),
                'user-name' => $user->getUsername(),
                'group-id' => $groupId,
            ],
        ]);

        return $viewButton.$removeButton;
    }
}
