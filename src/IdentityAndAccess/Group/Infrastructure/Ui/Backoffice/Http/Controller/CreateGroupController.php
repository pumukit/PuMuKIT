<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Controller;

use App\IdentityAndAccess\Group\Application\Create\CreateGroupRequest;
use App\IdentityAndAccess\Group\Application\Create\CreateGroupService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CreateGroupController extends AbstractController
{
    public function __construct(
        private readonly CreateGroupService $createGroupService
    ) {}

    public function __invoke(Request $request): Response
    {
        $name = $request->request->get('name');

        if (empty($name)) {
            $this->addFlash('error', 'group.create.error.name_required');

            return $this->redirectToRoute('group_list');
        }

        $key = $this->generateKeyFromName($name);

        try {
            $dto = new CreateGroupRequest(
                key: $key,
                name: $name,
                comments: '',
                origin: 'local'
            );

            $response = ($this->createGroupService)($dto);

            $this->addFlash('success', 'group.create.success');

            return $this->redirectToRoute('group_view', [
                'id' => $response->group->getId(),
                'tab' => 'edit',
            ]);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'already exists')) {
                $this->addFlash('error', 'group.create.error.key_exists');
            } else {
                $this->addFlash('error', 'group.create.error.generic');
            }

            return $this->redirectToRoute('group_list');
        } catch (\Exception $e) {
            $this->addFlash('error', 'group.create.error.generic');

            return $this->redirectToRoute('group_list');
        }
    }

    private function generateKeyFromName(string $name): string
    {
        $key = strtolower($name);
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);

        return trim($key, '_');
    }
}
