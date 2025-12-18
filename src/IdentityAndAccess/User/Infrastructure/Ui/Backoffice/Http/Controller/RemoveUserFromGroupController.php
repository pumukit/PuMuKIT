<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Infrastructure\Ui\Backoffice\Http\Controller;

use App\IdentityAndAccess\User\Application\RemoveUserFromGroup\RemoveUserFromGroupRequest;
use App\IdentityAndAccess\User\Application\RemoveUserFromGroup\RemoveUserFromGroupService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class RemoveUserFromGroupController extends AbstractController
{
    public function __construct(
        private readonly RemoveUserFromGroupService $service
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        try {
            $userId = (string) $request->request->get('user_id');

            $removeRequest = new RemoveUserFromGroupRequest($id, $userId);
            $response = ($this->service)($removeRequest);

            $this->addFlash('success', sprintf('User "%s" removed from the group.', $response->username));
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('group_view', ['id' => $id, 'tab' => 'users']);
    }
}
