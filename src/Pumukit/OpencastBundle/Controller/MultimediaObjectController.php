<?php

namespace Pumukit\OpencastBundle\Controller;

use Pumukit\OpencastBundle\Form\Type\MultimediaObjectType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/admin/opencast/mm")
 * @Security("is_granted('ROLE_ACCESS_IMPORTER')")
 */
class MultimediaObjectController extends Controller
{
    /**
     * @Route("/index/{id}", name="pumukit_opencast_mm_index")
     * @Template
     */
    public function indexAction(MultimediaObject $multimediaObject, Request $request)
    {
        $generateSbs = false;
        if ($this->container->hasParameter('pumukit_opencast.sbs.generate_sbs')) {
            $generateSbs = $this->container->getParameter('pumukit_opencast.sbs.generate_sbs');
        }
        $sbsProfile = '';
        if ($this->container->hasParameter('pumukit_opencast.sbs.profile')) {
            $sbsProfile = $this->container->getParameter('pumukit_opencast.sbs.profile');
        }
        $opencastClient = $this->get('pumukit_opencast.client');

        return [
            'mm' => $multimediaObject,
            'generate_sbs' => $generateSbs,
            'sbs_profile' => $sbsProfile,
            'player' => $opencastClient->getPlayerUrl(),
        ];
    }

    /**
     * @Route("/update/{id}", name="pumukit_opencast_mm_update")
     * @Template
     */
    public function updateAction(MultimediaObject $multimediaObject, Request $request)
    {
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $form = $this->createForm(new MultimediaObjectType($translator, $locale), $multimediaObject);
        if ($request->isMethod('PUT') || $request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                try {
                    $multimediaObject = $this->get('pumukitschema.multimedia_object')->updateMultimediaObject($multimediaObject);
                } catch (\Exception $e) {
                    return new Response($e->getMessage(), 400);
                }

                return $this->redirect($this->generateUrl('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]));
            }
        }

        return [
            'form' => $form->createView(),
            'multimediaObject' => $multimediaObject,
        ];
    }

    /**
     * @Route("/info/{id}", name="pumukit_opencast_mm_info")
     * @Template
     */
    public function infoAction(MultimediaObject $multimediaObject, Request $request)
    {
        $presenterDeliveryUrl = '';
        $presentationDeliveryUrl = '';
        $presenterDeliveryTrack = $multimediaObject->getTrackWithTag('presenter/delivery');
        $presentationDeliveryTrack = $multimediaObject->getTrackWithTag('presentation/delivery');
        if (null !== $presenterDeliveryTrack) {
            $presenterDeliveryUrl = $presenterDeliveryTrack->getUrl();
        }
        if (null !== $presentationDeliveryTrack) {
            $presentationDeliveryUrl = $presentationDeliveryTrack->getUrl();
        }

        return [
            'presenter_delivery_url' => $presenterDeliveryUrl,
            'presentation_delivery_url' => $presentationDeliveryUrl,
        ];
    }

    /**
     * @Route("/generatesbs/{id}", name="pumukit_opencast_mm_generatesbs")
     */
    public function generateSbsAction(MultimediaObject $multimediaObject, Request $request)
    {
        $opencastUrls = $this->get('pumukit_opencast.import')->getOpencastUrls($multimediaObject->getProperty('opencast'));
        $this->get('pumukit_opencast.job')->generateSbsTrack($multimediaObject, $opencastUrls);

        return $this->redirect($this->generateUrl('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]));
    }
}
