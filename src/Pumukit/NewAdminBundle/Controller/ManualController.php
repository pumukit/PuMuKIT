<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ACCESS_MULTIMEDIA_SERIES')]
class ManualController extends AbstractController implements NewAdminControllerInterface
{
    /**
     * @Route("/manual")
     */
    #[Template('@PumukitNewAdmin/Manual/index.html.twig')]
    public function indexAction()
    {
        return [];
    }
}
