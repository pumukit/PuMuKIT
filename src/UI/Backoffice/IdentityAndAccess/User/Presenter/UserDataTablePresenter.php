<?php

declare(strict_types=1);

namespace App\UI\Backoffice\IdentityAndAccess\User\Presenter;

use App\IdentityAndAccess\User\Domain\Repository\UserRepositoryInterface;
use App\UI\Backoffice\Shared\Helpers\BooleanIcon;
use App\UI\Backoffice\Shared\Helpers\DateFormat;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class UserDataTablePresenter
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
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
        return $this->twig->render('@Shared/Views/components/table/_datatable_actions.html.twig', [
            'actions' => [
                [
                    'type' => 'link',
                    'url' => $this->router->generate('user_view', ['id' => $user->getId()]),
                    'style' => 'info',
                    'icon' => 'eye',
                    'title' => 'View',
                ],
                [
                    'type' => 'link',
                    'url' => $this->router->generate('user_update', ['id' => $user->getId()]),
                    'style' => 'warning',
                    'icon' => 'edit',
                    'title' => 'Edit'
                ],
                [
                    'type' => 'form',
                    'url' => $this->router->generate('user_delete', ['id' => $user->getId()]),
                    'style' => 'danger',
                    'icon' => 'trash',
                    'title' => 'Delete',
                    'confirm' => 'Are you sure you want to delete this user?',
                ],
            ],
        ]);
    }
}
