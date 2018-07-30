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
class TagController extends Controller implements NewAdminController
{
    /**
     * @Template
     */
    public function indexAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository('PumukitSchemaBundle:Tag');

        $root_name = 'ROOT';
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
        try {
            $this->get('pumukitschema.tag')->deleteTag($tag);
        } catch (\Exception $e) {
            $msg = sprintf('Tag with children (%d) and multimedia objects (%d)',
                           count($tag->getChildren()),
                           $tag->getNumberMultimediaObjects());

            return new JsonResponse(array('status' => $msg), JsonResponse::HTTP_CONFLICT);
        }

        return new JsonResponse(array('status' => 'Deleted'), 200);
    }

    /**
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag")
     * @Template
     */
    public function updateAction(Tag $tag, Request $request)
    {
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $form = $this->createForm(new TagType($translator, $locale), $tag);

        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
            try {
                $this->get('pumukitschema.tag')->updateTag($tag);
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => $e->getMessage()), JsonResponse::HTTP_CONFLICT);
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
                return new JsonResponse(array('status' => $e->getMessage()), JsonResponse::HTTP_CONFLICT);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_tag_list'));
        }

        return array('tag' => $tag, 'form' => $form->createView());
    }

    /**
     * List action.
     *
     * @Template
     */
    public function listAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository('PumukitSchemaBundle:Tag');

        $root_name = 'ROOT';
        $root = $repo->findOneByCod($root_name);

        if (null !== $root) {
            $children = $root->getChildren();
        } else {
            $children = array();
        }

        return array(
            'root' => $root,
            'children' => $children,
        );
    }

    public function batchDeleteAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository('PumukitSchemaBundle:Tag');

        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $tags = array();
        $tagsWithChildren = array();
        foreach ($ids as $id) {
            $tag = $repo->find($id);
            if ($this->get('pumukitschema.tag')->canDeleteTag($tag)) {
                $tags[] = $tag;
            } else {
                $tagsWithChildren[] = $tag;
            }
        }

        if (0 !== count($tagsWithChildren)) {
            $message = '';
            foreach ($tagsWithChildren as $tag) {
                $message .= "Tag '".$tag->getCod()."' with children (".count($tag->getChildren()).'). ';
            }

            return new JsonResponse(array('status' => $message), JsonResponse::HTTP_CONFLICT);
        } else {
            foreach ($tags as $tag) {
                $dm->remove($tag);
            }
            $dm->flush();
        }

        $this->addFlash('success', 'delete');

        return $this->redirect($this->generateUrl('pumukitnewadmin_tag_list'));
    }
}
