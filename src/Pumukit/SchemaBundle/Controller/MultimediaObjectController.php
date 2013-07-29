<?php

namespace Pumukit\SchemaBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Entity\MultimediaObject;
use Pumukit\SchemaBundle\Form\MultimediaObjectType;

/**
 * MultimediaObject controller.
 *
 * @Route("/multimediaobject")
 */
class MultimediaObjectController extends Controller
{

    /**
     * Lists all MultimediaObject entities.
     *
     * @Route("/", name="multimediaobject")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('PumukitSchemaBundle:MultimediaObject')->findAll();

        return array(
            'entities' => $entities
        );
    }
    /**
     * Creates a new MultimediaObject entity.
     *
     * @Route("/", name="multimediaobject_create")
     * @Method("POST")
     * @Template("PumukitSchemaBundle:MultimediaObject:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity  = new MultimediaObject();
        $form = $this->createForm(new MultimediaObjectType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('multimediaobject_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView()
        );
    }

    /**
     * Displays a form to create a new MultimediaObject entity.
     *
     * @Route("/new", name="multimediaobject_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new MultimediaObject();
        $form   = $this->createForm(new MultimediaObjectType(), $entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView()
        );
    }

    /**
     * Finds and displays a MultimediaObject entity.
     *
     * @Route("/{id}", name="multimediaobject_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('PumukitSchemaBundle:MultimediaObject')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MultimediaObject entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView()
        );
    }

    /**
     * Displays a form to edit an existing MultimediaObject entity.
     *
     * @Route("/{id}/edit", name="multimediaobject_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('PumukitSchemaBundle:MultimediaObject')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MultimediaObject entity.');
        }

        $editForm = $this->createForm(new MultimediaObjectType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView()
        );
    }

    /**
     * Edits an existing MultimediaObject entity.
     *
     * @Route("/{id}", name="multimediaobject_update")
     * @Method("PUT")
     * @Template("PumukitSchemaBundle:MultimediaObject:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('PumukitSchemaBundle:MultimediaObject')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MultimediaObject entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new MultimediaObjectType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('multimediaobject_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView()
        );
    }
    /**
     * Deletes a MultimediaObject entity.
     *
     * @Route("/{id}", name="multimediaobject_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('PumukitSchemaBundle:MultimediaObject')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find MultimediaObject entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('multimediaobject'));
    }

    /**
     * Creates a form to delete a MultimediaObject entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}
