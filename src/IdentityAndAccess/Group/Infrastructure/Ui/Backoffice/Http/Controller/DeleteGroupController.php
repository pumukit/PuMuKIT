<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Controller;

use App\IdentityAndAccess\Group\Application\Delete\DeleteGroupRequest;
use App\IdentityAndAccess\Group\Application\Delete\DeleteGroupService;
use App\Shared\Domain\LoggerInterface;
use App\Shared\Domain\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class DeleteGroupController extends AbstractController
{
    public function __construct(
        private readonly DeleteGroupService $deleteGroupService,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator
    ) {}

    public function __invoke(string $id): RedirectResponse
    {
        try {
            $requestDto = new DeleteGroupRequest($id);

            $response = ($this->deleteGroupService)($requestDto);

            if ($response->success) {
                $this->addFlash('success', $this->translator->trans('group.delete.success', [], 'group'));
            } else {
                $this->addFlash('danger', $this->translator->trans('group.delete.error', [], 'group'));
            }

            return $this->redirectToRoute('group_list');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $this->translator->trans($e->getMessage(), [], 'group'));

            return $this->redirectToRoute('group_list');
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error deleting group', [
                'groupId' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlash('danger', $this->translator->trans('group.delete.error', [], 'group'));

            return $this->redirectToRoute('group_list');
        }
    }
}
