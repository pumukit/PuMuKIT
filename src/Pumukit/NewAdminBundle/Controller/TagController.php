<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\NewAdminBundle\Form\Type\TagType;

/**
 * @Security("is_granted('ROLE_ACCESS_TAGS')")
 */
class TagController extends Controller
{
    /**
     *
     * @Template
     */
    public function indexAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository('PumukitSchemaBundle:Tag');

        $root_name = "ROOT";
        $root = $repo->findOneByCod($root_name);

        if (null !== $root) {
            $children = $root->getChildren();
        } else {
            $children = array();
        }

        return array('root' => $root,
           'children' => $children, );
    }

    /**
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag")
     * @Template
     */
    public function childrenAction(Tag $tag, Request $request)
    {
        return array('tag' => $tag,
           'children' => $tag->getChildren(), );
    }

    /**
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag")
     */
    public function deleteAction(Tag $tag, Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        if (0 == $num = count($tag->getChildren())) {
            $dm->remove($tag);
            $dm->flush();

            return new JsonResponse(array("status" => "Deleted"), 200);
        }

        return new JsonResponse(array("status" => "Tag with children (".$num.")"), 404);
    }

    /**
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag")
     * @Template
     */
    public function updateAction(Tag $tag, Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $form = $this->createForm(new TagType($translator, $locale), $tag);

        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
            try {
                $tag = $this->get('pumukitschema.tag')->updateTag($tag);
            } catch (\Exception $e) {
                return new JsonResponse(array("status" => $e->getMessage()), 409);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_tag_list'));
        }

        return array('tag' => $tag, 'form' => $form->createView());
    }

    /**
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"id" = "parent"})
     * @Template
     */
    public function createAction(Tag $parent, Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository('PumukitSchemaBundle:Tag');

        $tag = new Tag();
        $tag->setParent($parent);

        $translator = $this->get('translator');
        $locale = $request->getLocale();

        $form = $this->createForm(new TagType($translator, $locale), $tag);

        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
            try {
                $dm->persist($tag);
                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(array("status" => $e->getMessage()), 409);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_tag_list'));
        }

        return array('tag' => $tag, 'form' => $form->createView());
    }

    /**
     * List action
     * @Template
     */
    public function listAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository('PumukitSchemaBundle:Tag');

        $root_name = "ROOT";
        $root = $repo->findOneByCod($root_name);

        if (null !== $root) {
            $children = $root->getChildren();
        } else {
            $children = array();
        }

        return array(
                     'root' => $root,
                     'children' => $children
                     );
    }

    public function batchDeleteAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository('PumukitSchemaBundle:Tag');

        $ids = $this->getRequest()->get('ids');

        if ('string' === gettype($ids)){
            $ids = json_decode($ids, true);
        }

        $tags = array();
        $tagsWithChildren = array();
        foreach ($ids as $id) {
            $tag = $repo->find($id);
            if (0 == count($tag->getChildren())) {
                $tags[] = $tag;
            }else{
                $tagsWithChildren[] = $tag;
            }
        }

        if (0 !== count($tagsWithChildren)){
            $message = '';
            foreach($tagsWithChildren as $tag){
                $message .= "Tag '".$tag->getCod()."' with children (".count($tag->getChildren())."). ";
            }

            return new JsonResponse(array("status" => $message), 404);
        }else{
            foreach ($tags as $tag){
                $dm->remove($tag);
            }
            $dm->flush();
        }

        $this->addFlash('success', 'delete');

        return $this->redirect($this->generateUrl('pumukitnewadmin_tag_list'));
    }
}
