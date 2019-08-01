<?php

namespace Pumukit\TemplateBundle\Controller;

use Pumukit\TemplateBundle\Document\Template as PumukitTemplate;
use Pumukit\TemplateBundle\Form\TemplateType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CrudController extends Controller
{
    /**
     * @Route("/admin/templates/")
     * @Template("PumukitTemplateBundle:Crud:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $repository = $this->get('doctrine_mongodb')->getRepository('PumukitTemplateBundle:Template');
        $templates = $repository->findAll();

        $active = null;
        if ($activeName = $request->get('active')) {
            $actives = array_filter(
                $templates,
                function ($t) use ($activeName) {
                    return $t->getName() == $activeName;
                }
            );
            $active = current($actives);
        }

        if (!$active) {
            if (count($templates) > 0) {
                $active = $templates[0];
            } else {
                $active = null;
            }
        }

        $deleteForm = null;
        $editForm = null;
        if ($active) {
            $deleteForm = $this->createDeleteForm($active);
            $editForm = $this->createForm(TemplateType::class, $active);

            $editForm->handleRequest($request);

            if ($editForm->isSubmitted() && $editForm->isValid()) {
                $dm = $this->get('doctrine_mongodb.odm.document_manager');
                $dm->persist($active);
                $dm->flush($active);
            }
        }

        return [
            'templates' => $templates,
            'active' => $active,
            'delete_form' => $deleteForm ? $deleteForm->createView() : null,
            'edit_form' => $editForm ? $editForm->createView() : null,
        ];
    }

    /**
     * @Route("/admin/templates/create")
     */
    public function createAction()
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');

        $t = new PumukitTemplate();
        $t->setName(time());

        $dm->persist($t);
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukit_template_crud_index'));
    }

    /**
     * @Route("/admin/templates/delete/{id}")
     */
    public function deleteAction(PumukitTemplate $t)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');

        $dm->remove($t);
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukit_template_crud_index'));
    }

    /**
     * Creates a form to delete a a entity.
     */
    private function createDeleteForm(PumukitTemplate $a)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('pumukit_template_crud_delete', ['id' => $a->getId()]))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}
