<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\HeadAndTailService;
use Pumukit\SchemaBundle\Services\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_HEAD_AND_TAIL_MANAGER')")
 */
class HeadAndTailController extends AdminController
{
    private $headAndTailService;

    public function __construct(
        DocumentManager $documentManager,
        PaginationService $paginationService,
        FactoryService $factoryService,
        GroupService $groupService,
        UserService $userService,
        SessionInterface $session,
        TranslatorInterface $translator,
        HeadAndTailService $headAndTailService
    ) {
        parent::__construct(
            $documentManager,
            $paginationService,
            $factoryService,
            $groupService,
            $userService,
            $session,
            $translator
        );

        $this->headAndTailService = $headAndTailService;
    }

    /**
     * @Route("/headandtail/manager", name="pumukit_newadmin_head_and_tail")
     */
    public function indexAction(Request $request): Response
    {
        return $this->render('@PumukitNewAdmin/HeadAndTail/template.html.twig');
    }

    /**
     * @Route("/headandtail/manager/xhr/remove/{type}/{element}", name="pumukit_newadmin_head_and_tail_manager_remove_item")
     */
    public function removeAction(Request $request, string $type, string $element): JsonResponse
    {
        if ($this->headAndTailService->removeElement($type, $element)) {
            return new JsonResponse(['success' => "{$type} element removed"]);
        }

        return new JsonResponse(['error' => "{$type} element with id {$element} couldn't be removed"]);
    }
}
