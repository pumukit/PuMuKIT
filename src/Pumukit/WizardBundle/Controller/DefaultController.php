<?php

namespace Pumukit\WizardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Intl;

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
        $formData = $request->get('pumukitwizard_form_data');

        return array(
                     'form_data' => $formData
                     );
    }

    /**
     * @Template()
     */
    public function typeAction($id, Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data');

        $showPrevious = true;
        $seriesRepo = $this->get('doctrine_mongodb.odm.document_manager')
            ->getRepository('PumukitSchemaBundle:Series');
        $series = $seriesRepo->find($id);

        if ($series){
            $showPrevious = false;
            if (!$formData){
                $formData = array('series' => array(
                                                   'i18n_title' => $series->getI18nTitle(),
                                                   'i18n_description' => $series->getI18nDescription()
                                                   ));
            }
        }

        return array(
                     'series_id' => $id,
                     'form_data' => $formData,
                     'show_previous' => $showPrevious
                     );
    }

    /**
     * Option action
     */
    public function optionAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data');

        if ('multiple' == $formData['type']['option']){
            return $this->redirect($this->generateUrl('pumukitwizard_default_track', array('pumukitwizard_form_data' => $formData)));
        }

        return $this->redirect($this->generateUrl('pumukitwizard_default_multimediaobject', array('pumukitwizard_form_data' => $formData)));
    }

    /**
     * @Template()
     */
    public function multimediaobjectAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data');

        return array(
                     'form_data' => $formData
                     );
    }

    /**
     * @Template()
     */
    public function trackAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data');

        $masterProfiles = $this->get('pumukitencoder.profile')->getMasterProfiles(true);
        $factoryService = $this->get('pumukitschema.factory');
        $pubChannelsTags = $factoryService->getTagsByCod('PUBCHANNELS', true);

        $languages = Intl::getLanguageBundle()->getLanguageNames();

        return array(
                     'form_data' => $formData,
                     'master_profiles' => $masterProfiles,
                     'pub_channels' => $pubChannelsTags,
                     'languages' => $languages
                     );
    }
}