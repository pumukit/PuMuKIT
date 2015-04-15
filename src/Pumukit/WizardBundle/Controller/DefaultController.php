<?php

namespace Pumukit\WizardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
  // TODO complete all actions

    /**
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @Template()
     */
    public function seriesAction(Request $request)
    {
        $seriesData = array();

        $typeForm = $request->get('pumukitwizard_type');
        if ($typeForm){
          $seriesData = $typeForm['series'];
        }

        return array(
                     'series_data' => $seriesData
                     );
    }

    /**
     * @Template()
     */
    public function typeAction($id, Request $request)
    {
        $showPrevious = true;
        // TODO id of existent series
        if ('null' !== $id) $showPrevious = false;

        $seriesForm = array();
        if ($showPrevious){
          $seriesForm = $request->get('pumukitwizard_series');
        }

        return array(
                     'series_id' => $id,
                     'series_form' => $seriesForm,
                     'show_previous' => $showPrevious
                     );
    }
}
