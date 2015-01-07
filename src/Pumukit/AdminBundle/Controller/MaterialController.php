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

        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
            try {
                $materialService = $this->get('pumukitschema.material');
                if (null !== $request->get('url', null)){
                  $material->setUrl($request->get('url'));
                }elseif (null !== $request->get('file', null)){
                  $materialFile = $request->get('file')->getData();
                  $path = $materialFile->move($this->targetPath."/".$multimediaObject->getId(), $materialFile->getClientOriginalName());
                  $material->setUrl(str_replace($this->targetPath, $this->targetUrl, $path));
                }
                $multimediaObject = $materialService->addMaterialToMultimediaObject($multimediaObject, $material);
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }

            return $this->render('PumukitAdminBundle:Material:list.html.twig',
                 array(
                       'materials' => $multimediaObject->getMaterials(),
                       'mmId' => $multimediaObject->getId(),
                       )
                 );
        }

        return array(
             'material' => $material,
             'form' => $form->createView(),
             'mm' => $multimediaObject,
             );
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @Template
     */
    public function updateAction(MultimediaObject $multimediaObject, Request $request)
    {
        $material = $multimediaObject->getMaterialById($this->getRequest()->get('id'));
        $form = $this->createForm(new MaterialType(), $material);

        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
            try {
                $multimediaObject = $this->get('pumukitschema.material')->updateMaterialInMultimediaObject($multimediaObject, $material);
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }

            return $this->render('PumukitAdminBundle:Material:list.html.twig',
                 array(
                       'materials' => $multimediaObject->getMaterials(),
                       'mmId' => $multimediaObject->getId(),
                       )
                 );
        }

        return array(
             'material' => $material,
             'form' => $form->createView(),
             'mm' => $multimediaObject,
             );
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject")
     */
    public function uploadAction(MultimediaObject $multimediaObject, Request $request)
    {
        if ($request->files->has("file")) {
            $materialService = $this->get('pumukitschema.material');
            $media = $materialService->addMaterialFile($multimediaObject, $request->files->get("file"));

            return $this->render('PumukitAdminBundle:Material:upload.html.twig',
                 array('mm' => $multimediaObject));
        } elseif (($url = $request->get('url')) || ($url = $request->get('materialUrl'))) {
            $picService = $this->get('pumukitschema.mmspic');
            $multimediaObject = $picService->addPicUrl($multimediaObject, $url);

            return $this->render('PumukitAdminBundle:Material:list.html.twig',
                 array(
                       'materials' => $multimediaObject->getMaterials(),
                       'mmId' => $multimediaObject->getId(),
                       ));
        }

        return $this->render('PumukitAdminBundle:Material:list.html.twig',
                 array(
                   'materials' => $multimediaObject->getMaterials(),
                   'mmId' => $multimediaObject->getId(),
                   ));
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @Template("PumukitAdminBundle:Material:list.html.twig")
     */
    public function deleteAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.material')->removeMaterialFromMultimediaObject($multimediaObject, $this->getRequest()->get('id'));

        $this->addFlash('success', 'delete');

        return array(
             'materials' => $multimediaObject->getMaterials(),
             'mmId' => $multimediaObject->getId(),
             );
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @Template("PumukitAdminBundle:Material:list.html.twig")
     */
    public function upAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.material')->upMaterialInMultimediaObject($multimediaObject, $this->getRequest()->get('id'));

        $this->addFlash('success', 'delete');

        return array(
             'mmId' => $multimediaObject->getId(),
             'materials' => $multimediaObject->getMaterials(),
             );
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @Template("PumukitAdminBundle:Material:list.html.twig")
     */
    public function downAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.material')->downMaterialInMultimediaObject($multimediaObject, $this->getRequest()->get('id'));

        $this->addFlash('success', 'delete');

        return array(
             'mmId' => $multimediaObject->getId(),
             'materials' => $multimediaObject->getMaterials(),
             );
    }
}
