<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Controller;

use App\IdentityAndAccess\Group\Domain\Repository\GroupRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AddUserToGroupController extends AbstractController
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly DocumentManager $documentManager
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        $group = $this->groupRepository->find($id);

        if (!$group instanceof Group) {
            $this->addFlash('error', 'Group not found.');

            return $this->redirectToRoute('group_list');
        }

        $userId = $request->request->get('user_id');

        if (empty($userId)) {
            $this->addFlash('error', 'User ID is required.');

            return $this->redirectToRoute('group_view', ['id' => $id, 'tab' => 'users']);
        }

        $user = $this->documentManager->getRepository(User::class)->find($userId);

        if (!$user instanceof User) {
            $this->addFlash('error', 'User not found.');

            return $this->redirectToRoute('group_view', ['id' => $id, 'tab' => 'users']);
        }

        // Check if user is already in the group
        if ($user->getGroups()->contains($group)) {
            $this->addFlash('warning', sprintf('User "%s" is already in this group.', $user->getUsername()));

            return $this->redirectToRoute('group_view', ['id' => $id, 'tab' => 'users']);
        }

        // Add user to group
        $user->addGroup($group);
        $this->documentManager->persist($user);
        $this->documentManager->flush();

        $this->addFlash('success', sprintf('User "%s" has been added to the group.', $user->getUsername()));

        return $this->redirectToRoute('group_view', ['id' => $id, 'tab' => 'users']);
    }
}
