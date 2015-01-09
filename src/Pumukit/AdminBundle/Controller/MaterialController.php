<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\AdminBundle\Form\Type\MaterialType;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class MaterialController extends Controller
{
    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject")
     * @Template
     */
    public function createAction(MultimediaObject $multimediaObject, Request $request)
    {
        $material = new Material();
        $form = $this->createForm(new MaterialType(), $material);

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
        $material = $multimediaObject->getMaterialById($request->get('id'));
        $form = $this->createForm(new MaterialType(), $material);

        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
            try {
                $multimediaObject = $this->get('pumukitschema.material')->updateMaterialInMultimediaObject($multimediaObject);
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }

            return $this->redirect($this->generateUrl('pumukitadmin_material_list', array('id' => $multimediaObject->getId())));
        }

        return $this->render('PumukitAdminBundle:Material:update.html.twig', 
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
        $formData = $request->get('pumukitadmin_material', array());

        $materialService = $this->get('pumukitschema.material');
        if (($request->files->has('file')) && (!$request->get('url', null))) {
            $multimediaObject = $materialService->addMaterialFile($multimediaObject, $request->files->get('file'), $formData);
        } elseif ($request->get('url', null)) {
          $multimediaObject = $materialService->addMaterialUrl($multimediaObject, $request->get('url'), $formData);
        }

        return array('mm' => $multimediaObject);
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function deleteAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.material')->removeMaterialFromMultimediaObject($multimediaObject, $this->getRequest()->get('id'));

        $this->addFlash('success', 'delete');

        return $this->redirect($this->generateUrl('pumukitadmin_material_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function upAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.material')->upMaterialInMultimediaObject($multimediaObject, $this->getRequest()->get('id'));

        $this->addFlash('success', 'up');

        return $this->redirect($this->generateUrl('pumukitadmin_material_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function downAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.material')->downMaterialInMultimediaObject($multimediaObject, $this->getRequest()->get('id'));

        $this->addFlash('success', 'down');

        return $this->redirect($this->generateUrl('pumukitadmin_material_list', array('id' => $multimediaObject->getId())));
    }

    /**
     * @Template("PumukitAdminBundle:Material:list.html.twig")
     */
    public function listAction(MultimediaObject $multimediaObject)
    {
        return array(
                     'mmId' => $multimediaObject->getId(),
                     'materials' => $multimediaObject->getMaterials()
                     );
    }
}