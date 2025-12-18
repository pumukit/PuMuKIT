<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Infrastructure\Ui\Backoffice\Http\Controller;

use App\IdentityAndAccess\User\Application\AddUserToGroup\AddUserToGroupRequest;
use App\IdentityAndAccess\User\Application\AddUserToGroup\AddUserToGroupService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AddUserToGroupController extends AbstractController
{
    public function __construct(
        private readonly AddUserToGroupService $service
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        try {
            $userId = (string) $request->request->get('user_id');

            $addUserRequest = new AddUserToGroupRequest($id, $userId);
            $response = ($this->service)($addUserRequest);

            $this->addFlash('success', sprintf('User "%s" added to the group.', $response->username));
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('group_view', ['id' => $id, 'tab' => 'users']);
    }
}
