<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Controller;

use App\IdentityAndAccess\Group\Application\Update\UpdateGroupRequest;
use App\IdentityAndAccess\Group\Application\Update\UpdateGroupService;
use App\IdentityAndAccess\Group\Domain\Repository\GroupRepositoryInterface;
use App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Form\GroupUpdateType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UpdateGroupController extends AbstractController
{
    public function __construct(
        private readonly UpdateGroupService $service,
        private readonly GroupRepositoryInterface $repository
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        $group = $this->repository->find($id);
        if (!$group) {
            $this->addFlash('error', 'group.error.not_found');

            return $this->redirectToRoute('group_list');
        }

        $updateRequest = UpdateGroupRequest::fromGroup($group);
        $form = $this->createForm(GroupUpdateType::class, $updateRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                ($this->service)($updateRequest);
                $this->addFlash('success', 'group.flash.updated');

                return $this->redirectToRoute('group_view', ['id' => $id, 'tab' => 'general']);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('group_view', [
            'id' => $group->getId(),
            'tab' => 'edit',
        ]);
    }
}
