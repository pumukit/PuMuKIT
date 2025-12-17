<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Controller;

use App\IdentityAndAccess\Group\Domain\Repository\GroupRepositoryInterface;
use App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Presenter\GroupUserDataTablePresenter;
use Pumukit\SchemaBundle\Document\Group;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class GroupUsersDataController extends AbstractController
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly GroupUserDataTablePresenter $presenter
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $group = $this->groupRepository->find($id);

        if (!$group instanceof Group) {
            return $this->json([
                'total' => 0,
                'rows' => [],
            ]);
        }

        $users = $group->getUsers();

        $rows = [];
        foreach ($users as $user) {
            $rows[] = $this->presenter->present($user, $id);
        }

        return $this->json([
            'total' => count($rows),
            'rows' => $rows,
        ]);
    }
}
