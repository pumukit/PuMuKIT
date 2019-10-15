<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller implements NewAdminControllerInterface
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
