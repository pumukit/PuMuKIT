<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController implements NewAdminControllerInterface
{
    /**
     * @Route("/", name="pumukit_newadmin_index")
     * @Route("/default")
     */
    public function indexAction()
    {
        return $this->redirectToRoute('pumukitnewadmin_series_index');
    }
}
