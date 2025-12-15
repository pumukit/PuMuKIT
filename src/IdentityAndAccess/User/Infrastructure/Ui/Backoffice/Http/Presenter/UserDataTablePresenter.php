<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Infrastructure\Ui\Backoffice\Http\Presenter;

use App\Shared\Infrastructure\Ui\Backoffice\Http\Helpers\BooleanIcon;
use App\Shared\Infrastructure\Ui\Backoffice\Http\Helpers\DateFormat;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class UserDataTablePresenter
{
    public function __construct(
        private RouterInterface $router,
        private Environment $twig,
    ) {}

    public function present(User $user): array
    {
        return [
            'id' => $user->getId(),
            'fullName' => $user->getFullName(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'origin' => $user->getOrigin(),
            'enabled' => BooleanIcon::convert($user->isEnabled()),
            'last_login' => DateFormat::format($user->getLastLoginAttempt()),
            'actions' => $this->renderActions($user),
        ];
    }

    private function renderActions(User $user): string
    {
        $viewButton = $this->twig->render('@Shared/Views/components/table/buttons/_view_button.html.twig', [
            'url' => $this->router->generate('user_view', ['id' => $user->getId()]),
        ]);

        $editButton = $this->twig->render('@Shared/Views/components/table/buttons/_edit_button.html.twig', [
            'url' => $this->router->generate('user_update', ['id' => $user->getId()]),
        ]);

        $deleteButton = $this->twig->render('@Shared/Views/components/table/buttons/_delete_button.html.twig', [
            'url' => $this->router->generate('user_delete', ['id' => $user->getId()]),
            'confirm' => 'Are you sure you want to delete this user?',
        ]);

        return $viewButton.$editButton.$deleteButton;
    }
}
