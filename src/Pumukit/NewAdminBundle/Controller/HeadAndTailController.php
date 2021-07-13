<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Security("is_granted('ROLE_ACCESS_HEAD_AND_TAIL_MANAGER')")
 */
class HeadAndTailController extends AdminController implements NewAdminControllerInterface
{
    /**
     * @Route("/headandtail/manager", name="pumukit_newadmin_head_and_tail")
     */
    public function indexAction(Request $request): Response
    {
        return $this->render('PumukitNewAdminBundle:HeadAndTail:template.html.twig');
    }

    /**
     * @Route("/headandtail/manager/xhr/remove/{type}/{element}", name="pumukit_newadmin_head_and_tail_manager_remove_item")
     */
    public function removeAction(Request $request, string $type, string $element): JsonResponse
    {
        $headAndTailService = $this->get('pumukit_schema.head_and_tail');

        if ($headAndTailService->removeElement($type, $element)) {
            return new JsonResponse(['success' => "${type} element removed"]);
        }

        return new JsonResponse(['error' => "${type} element with id ${element} couldn't be removed"]);
    }
}
