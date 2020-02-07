<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\NewAdminBundle\Form\Type\TagType;
use Pumukit\SchemaBundle\Document\Tag;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Security("is_granted('ROLE_ACCESS_TAGS')")
 */
class TagController extends AbstractController implements NewAdminControllerInterface
{
    /**
     * @Template("PumukitNewAdminBundle:Tag:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository(Tag::class);

        $root_name = 'ROOT';
        $root = $repo->findOneByCod($root_name);

        if (null !== $root) {
            $children = $root->getChildren();
        } else {
            $children = [];
        }

        return ['root' => $root,
            'children' => $children, ];
    }

    /**
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag")
     * @Template("PumukitNewAdminBundle::Tag:children.html.twig")
     */
    public function childrenAction(Tag $tag, Request $request)
    {
        return ['tag' => $tag,
            'children' => $tag->getChildren(), ];
    }

    /**
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag")
     */
    public function deleteAction(Tag $tag, Request $request)
    {
        try {
            $this->tagService->deleteTag($tag);
        } catch (\Exception $e) {
            $msg = sprintf(
                'Tag with children (%d) and multimedia objects (%d)',
                count($tag->getChildren()),
                $tag->getNumberMultimediaObjects()
            );

            return new JsonResponse(['status' => $msg], JsonResponse::HTTP_CONFLICT);
        }

        return new JsonResponse(['status' => 'Deleted'], 200);
    }

    /**
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag")
     * @Template("PumukitNewAdminBundle:Tag:update.html.twig")
     */
    public function updateAction(Tag $tag, Request $request)
    {

        $locale = $request->getLocale();
        $form = $this->createForm(TagType::class, $tag, ['translator' => $this->translationService, 'locale' => $locale]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($request->isMethod('PUT') || $request->isMethod('POST'))) {
            try {
                $this->tagService->updateTag($tag);
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], JsonResponse::HTTP_CONFLICT);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_tag_list'));
        }

        return ['tag' => $tag, 'form' => $form->createView()];
    }

    /**
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"id" = "parent"})
     * @Template("PumukitNewAdminBundle:Tag:create.html.twig")
     */
    public function createAction(Tag $parent, Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $tag = new Tag();
        $tag->setParent($parent);


        $locale = $request->getLocale();

        $form = $this->createForm(TagType::class, $tag, ['translator' => $this->translationService, 'locale' => $locale]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($request->isMethod('PUT') || $request->isMethod('POST'))) {
            try {
                $dm->persist($tag);
                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], JsonResponse::HTTP_CONFLICT);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_tag_list'));
        }

        return ['tag' => $tag, 'form' => $form->createView()];
    }

    /**
     * List action.
     *
     * @Template("PumukitNewAdminBundle:Tag:list.html.twig")
     */
    public function listAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository(Tag::class);

        $root_name = 'ROOT';
        $root = $repo->findOneByCod($root_name);

        if (null !== $root) {
            $children = $root->getChildren();
        } else {
            $children = [];
        }

        return [
            'root' => $root,
            'children' => $children,
        ];
    }

    public function batchDeleteAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository(Tag::class);

        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $tags = [];
        $tagsWithChildren = [];
        foreach ($ids as $id) {
            $tag = $repo->find($id);
            if ($this->tagService->canDeleteTag($tag)) {
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

            return new JsonResponse(['status' => $message], JsonResponse::HTTP_CONFLICT);
        }
        foreach ($tags as $tag) {
            $dm->remove($tag);
        }
        $dm->flush();

        $this->addFlash('success', 'delete');

        return $this->redirect($this->generateUrl('pumukitnewadmin_tag_list'));
    }
}
