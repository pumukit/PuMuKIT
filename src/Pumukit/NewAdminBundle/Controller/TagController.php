<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\NewAdminBundle\Form\Type\TagType;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Services\TagService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_TAGS')")
 */
class TagController extends AbstractController implements NewAdminControllerInterface
{
    /** @var DocumentManager */
    private $documentManager;
    /** @var TagService */
    private $tagService;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        DocumentManager $documentManager,
        TagService $tagService,
        TranslatorInterface $translator
    ) {
        $this->documentManager = $documentManager;
        $this->tagService = $tagService;
        $this->translator = $translator;
    }

    /**
     * @Template("@PumukitNewAdmin/Tag/index.html.twig")
     */
    public function indexAction()
    {
        $repo = $this->documentManager->getRepository(Tag::class);

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
     * @Template("@PumukitNewAdmin/Tag/children.html.twig")
     */
    public function childrenAction(Tag $tag)
    {
        return [
            'tag' => $tag,
            'children' => $tag->getChildren(),
        ];
    }

    public function deleteAction(Tag $tag)
    {
        try {
            $this->tagService->deleteTag($tag);
        } catch (\Exception $e) {
            $msg = sprintf(
                'Tag with children (%d) and multimedia objects (%d)',
                is_countable($tag->getChildren()) ? count($tag->getChildren()) : 0,
                $tag->getNumberMultimediaObjects()
            );

            return new JsonResponse(['status' => $msg], JsonResponse::HTTP_CONFLICT);
        }

        return new JsonResponse(['status' => 'Deleted'], 200);
    }

    /**
     * @Template("@PumukitNewAdmin/Tag/update.html.twig")
     */
    public function updateAction(Request $request, Tag $tag)
    {
        $locale = $request->getLocale();
        $form = $this->createForm(TagType::class, $tag, ['translator' => $this->translator, 'locale' => $locale]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($request->isMethod('PUT') || $request->isMethod('POST'))) {
            try {
                $this->tagService->updateTag($tag);
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], JsonResponse::HTTP_CONFLICT);
            }

            return $this->redirectToRoute('pumukitnewadmin_tag_list');
        }

        return ['tag' => $tag, 'form' => $form->createView()];
    }

    /**
     * @ParamConverter("tag", options={"id" = "parent"})
     * @Template("@PumukitNewAdmin/Tag/create.html.twig")
     */
    public function createAction(Request $request, Tag $parent)
    {
        $tag = new Tag();
        $tag->setParent($parent);

        $locale = $request->getLocale();

        $form = $this->createForm(TagType::class, $tag, ['translator' => $this->translator, 'locale' => $locale]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($request->isMethod('PUT') || $request->isMethod('POST'))) {
            try {
                $this->documentManager->persist($tag);
                $this->documentManager->flush();
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], JsonResponse::HTTP_CONFLICT);
            }

            return $this->redirectToRoute('pumukitnewadmin_tag_list');
        }

        return ['tag' => $tag, 'form' => $form->createView()];
    }

    /**
     * @Template("@PumukitNewAdmin/Tag/list.html.twig")
     */
    public function listAction()
    {
        $repo = $this->documentManager->getRepository(Tag::class);

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
        $repo = $this->documentManager->getRepository(Tag::class);

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
                $message .= "Tag '".$tag->getCod()."' with children (".(is_countable($tag->getChildren()) ? count($tag->getChildren()) : 0).'). ';
            }

            return new JsonResponse(['status' => $message], JsonResponse::HTTP_CONFLICT);
        }
        foreach ($tags as $tag) {
            $this->documentManager->remove($tag);
        }
        $this->documentManager->flush();

        $this->addFlash('success', 'delete');

        return $this->redirectToRoute('pumukitnewadmin_tag_list');
    }
}
