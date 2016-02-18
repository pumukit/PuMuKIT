<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller implements NewAdminController
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
      return $this->redirectToRoute('pumukitnewadmin_series_index');
    }
}
