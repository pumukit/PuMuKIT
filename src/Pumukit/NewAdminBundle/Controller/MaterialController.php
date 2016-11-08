<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\NewAdminBundle\Form\Type\MaterialType;
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class MaterialController extends Controller implements NewAdminController
{
    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject")
     * @Template
     */
    public function createAction(MultimediaObject $multimediaObject, Request $request)
    {
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $material = new Material();
        $form = $this->createForm(new MaterialType($translator, $locale), $material);

        return array(
                     'material' => $material,
                     'form' => $form->createView(),
                     'mm' => $multimediaObject,
                     );
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function updateAction(MultimediaObject $multimediaObject, Request $request)
    {
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $material = $multimediaObject->getMaterialById($request->get('id'));
        $form = $this->createForm(new MaterialType($translator, $locale), $material);

        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
            try {
                $multimediaObject = $this->get('pumukitschema.material')->updateMaterialInMultimediaObject($multimediaObject, $material);
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_material_list', array('id' => $multimediaObject->getId())));
        }

        return $this->render('PumukitNewAdminBundle:Material:update.html.twig',
                             array(
                                   'material' => $material,
                                   'form' => $form->createView(),
                                   'mmId' => $multimediaObject->getId(),
                                   ));
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject")
     * @Template
     */
    public function uploadAction(MultimediaObject $multimediaObject, Request $request)
    {
        $formData = $request->get('pumukitnewadmin_material', array());

        $materialService = $this->get('pumukitschema.material');
        try {
            if (empty($_FILES) && empty($_POST)) {
                throw new \Exception('PHP ERROR: File exceeds post_max_size ('.ini_get('post_max_size').')');
            }
            if (($request->files->has('file')) && (!$request->get('url', null))) {
                $multimediaObject = $materialService->addMaterialFile($multimediaObject, $request->files->get('file'), $formData);
            } elseif ($request->get('url', null)) {
                $multimediaObject = $materialService->addMaterialUrl($multimediaObject, $request->get('url'), $formData);
            }
        } catch (\Exception $e) {
            return array(
                         'mm' => $multimediaObject,
                         'uploaded' => 'failed',
                         'message' => $e->getMessage()
                         );
        }

        return array(
                     'mm' => $multimediaObject,
                     'uploaded' => 'success',
                     'message' => 'New Material added.'
                     );
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function deleteAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.material')->removeMaterialFromMultimediaObject($multimediaObject, $this->getRequest()->get('id'));

        return $this->redirect($this->generateUrl('pumukitnewadmin_material_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function upAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.material')->upMaterialInMultimediaObject($multimediaObject, $this->getRequest()->get('id'));

        $this->addFlash('success', 'up');

        return $this->redirect($this->generateUrl('pumukitnewadmin_material_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function downAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.material')->downMaterialInMultimediaObject($multimediaObject, $this->getRequest()->get('id'));

        $this->addFlash('success', 'down');

        return $this->redirect($this->generateUrl('pumukitnewadmin_material_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * @Template("PumukitNewAdminBundle:Material:list.html.twig")
     */
    public function listAction(MultimediaObject $multimediaObject)
    {
        return array(
                     'mmId' => $multimediaObject->getId(),
                     'materials' => $multimediaObject->getMaterials()
                     );
    }
}
