<?php

declare(strict_types=1);

namespace App\UI\Backoffice\IdentityAndAccess\Presenter;

use App\UI\Backoffice\Shared\Helpers\BooleanIcon;
use App\UI\Backoffice\Shared\Helpers\DateFormat;
use App\IdentityAndAccess\Domain\Repository\UserRepositoryInterface;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\Routing\RouterInterface;

final class UserDataTablePresenter
{
    public function __construct(
        private RouterInterface $router,
        private UserRepositoryInterface $userRepository
    ) {}

    public function present(User $user, string $scheme, string $host, string $locale): array
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
        $viewUrl = '#'; // $this->router->generate('user_view', ['id' => $user->getId()]);
        $deleteUrl = '#'; // $this->router->generate('user_delete', ['id' => $user->getId()]);

        return sprintf(
            '<div class="d-flex gap-1 justify-content-end">
                <a href="%s" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> View</a>
                <form action="%s" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this series?\');">
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="fa fa-trash"></i> Delete
                    </button>
                </form>
            </div>',
            $viewUrl,
            $deleteUrl
        );
    }
}
