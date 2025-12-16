<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Infrastructure\Ui\Backoffice\Http\Controller;

use App\IdentityAndAccess\Authorization\Application\View\ViewPermissionProfileRequest;
use App\IdentityAndAccess\Authorization\Application\View\ViewPermissionProfileService;
use App\IdentityAndAccess\Authorization\Infrastructure\Ui\Backoffice\Http\Event\PermissionProfileViewTabsEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ViewPermissionProfileController extends AbstractController
{
    public function __construct(
        private ViewPermissionProfileService $viewPermissionProfileService,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(Request $request, string $id, string $tab = 'general'): Response
    {
        $dto = new ViewPermissionProfileRequest($id, $tab);
        $response = ($this->viewPermissionProfileService)($dto);

        // Dispatch event to allow other modules to add tabs
        $tabsEvent = new PermissionProfileViewTabsEvent($response->permissionProfile);
        $this->eventDispatcher->dispatch($tabsEvent, PermissionProfileViewTabsEvent::NAME);

        return $this->render('@PermissionProfile/Views/view.html.twig', [
            'permissionProfile' => $response->permissionProfile,
            'tab' => $response->tab,
            'tabs' => $tabsEvent->getTabs(),
        ]);
    }
}
