<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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
}
