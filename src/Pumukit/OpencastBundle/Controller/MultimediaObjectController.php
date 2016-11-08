<?php

namespace Pumukit\OpencastBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\OpencastBundle\Services\OpencastService;
use Pumukit\OpencastBundle\Form\Type\MultimediaObjectType;

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

        return array(
                     'mm' => $multimediaObject,
                     'generate_sbs' => $generateSbs,
                     'sbs_profile' => $sbsProfile,
                     );
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
            if ($form->bind($request)->isValid()) {
                try {
                    $multimediaObject = $this->get('pumukitschema.factory')->updateMultimediaObject($multimediaObject);
                } catch (\Exception $e) {
                    return new Response($e->getMessage(), 400);
                }

                return $this->redirect($this->generateUrl('pumukitnewadmin_track_list', array('id' => $multimediaObject->getId())));
            } else {
                $errors = $this->get('validator')->validate($track);
                $textStatus = '';
                foreach ($errors as $error) {
                    $textStatus .= $error->getPropertyPath().' value '.$error->getInvalidValue().': '.$error->getMessage().'. ';
                }

                return new Response($textStatus, 409);
            }
        }

        return array(
                     'form' => $form->createView(),
                     'multimediaObject' => $multimediaObject,
                     );
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

        return array(
                     'presenter_delivery_url' => $presenterDeliveryUrl,
                     'presentation_delivery_url' => $presentationDeliveryUrl,
                     );
    }

    /**
     * @Route("/generatesbs/{id}", name="pumukit_opencast_mm_generatesbs")
     */
    public function generateSbsAction(MultimediaObject $multimediaObject, Request $request)
    {
        $opencastUrls = $this->get('pumukit_opencast.import')->getOpencastUrls($multimediaObject->getProperty('opencast'));
        $this->get('pumukit_opencast.job')->generateSbsTrack($multimediaObject, $opencastUrls);

        return $this->redirect($this->generateUrl('pumukitnewadmin_track_list', array('id' => $multimediaObject->getId())));
    }
}
