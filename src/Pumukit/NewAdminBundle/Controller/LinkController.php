<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Pumukit\SchemaBundle\Document\Link;
use Pumukit\NewAdminBundle\Form\Type\LinkType;
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class LinkController extends Controller implements NewAdminControllerInterface
{
    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject")
     * @Template
     */
    public function createAction(MultimediaObject $multimediaObject, Request $request)
    {
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $link = new Link();
        $form = $this->createForm(LinkType::class, $link, ['translator' => $translator, 'locale' => $locale]);

        $form->handleRequest($request);
        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->isValid()) {
            try {
                $multimediaObject = $this->get('pumukitschema.link')->addLinkToMultimediaObject($multimediaObject, $link);
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }

            return $this->render('PumukitNewAdminBundle:Link:list.html.twig',
                                 [
                                     'links' => $multimediaObject->getLinks(),
                                     'mmId' => $multimediaObject->getId(),
                                 ]
            );
        }

        return [
            'link' => $link,
            'form' => $form->createView(),
            'mm' => $multimediaObject,
        ];
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @Template
     */
    public function updateAction(MultimediaObject $multimediaObject, Request $request)
    {
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $link = $multimediaObject->getLinkById($request->get('id'));
        $form = $this->createForm(LinkType::class, $link, ['translator' => $translator, 'locale' => $locale]);

        $form->handleRequest($request);
        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->isValid()) {
            try {
                $multimediaObject = $this->get('pumukitschema.link')->updateLinkInMultimediaObject($multimediaObject, $link);
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }

            return $this->render('PumukitNewAdminBundle:Link:list.html.twig',
                                 [
                                     'links' => $multimediaObject->getLinks(),
                                     'mmId' => $multimediaObject->getId(),
                                 ]
            );
        }

        return [
            'link' => $link,
            'form' => $form->createView(),
            'mm' => $multimediaObject,
        ];
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @Template("PumukitNewAdminBundle:Link:list.html.twig")
     */
    public function deleteAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.link')->removeLinkFromMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'delete');

        return [
            'links' => $multimediaObject->getLinks(),
            'mmId' => $multimediaObject->getId(),
        ];
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @Template("PumukitNewAdminBundle:Link:list.html.twig")
     */
    public function upAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.link')->upLinkInMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'delete');

        return [
            'mmId' => $multimediaObject->getId(),
            'links' => $multimediaObject->getLinks(),
        ];
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @Template("PumukitNewAdminBundle:Link:list.html.twig")
     */
    public function downAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.link')->downLinkInMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'delete');

        return [
            'mmId' => $multimediaObject->getId(),
            'links' => $multimediaObject->getLinks(),
        ];
    }
}
