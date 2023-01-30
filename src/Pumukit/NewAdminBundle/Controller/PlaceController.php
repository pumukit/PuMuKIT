<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\NewAdminBundle\Form\Type\TagType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Services\TagService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_TAGS')")
 * @Route("/places")
 */
class PlaceController extends AbstractController implements NewAdminControllerInterface
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var DocumentManager */
    private $documentManager;

    /** @var TagService */
    private $tagService;

    public function __construct(
        TranslatorInterface $translator,
        DocumentManager $documentManager,
        TagService $tagService
    ) {
        $this->translator = $translator;
        $this->documentManager = $documentManager;
        $this->tagService = $tagService;
    }

    /**
     * @Route("/", name="pumukitnewadmin_places_index")
     * @Template("@PumukitNewAdmin/Place/index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $placeTag = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => 'PLACES']);
        $places = $this->documentManager->getRepository(Tag::class)->findBy(['parent.$id' => new ObjectId($placeTag->getId())], ['title.'.$request->getLocale() => 1]);

        return ['places' => $places];
    }

    /**
     * @Route("/parent/", name="pumukitnewadmin_places_parent")
     * @Template("@PumukitNewAdmin/Place/parent_list.html.twig")
     */
    public function parentAction(Request $request)
    {
        $placeTag = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => 'PLACES']);
        $places = $this->documentManager->getRepository(Tag::class)->findBy(['parent.$id' => new ObjectId($placeTag->getId())], ['title.'.$request->getLocale() => 1]);

        return ['places' => $places];
    }

    /**
     * @Route("/children/{id}", name="pumukitnewadmin_places_children")
     * @ParamConverter("tag", options={"mapping": {"id": "id"}})
     * @Template("@PumukitNewAdmin/Place/children_list.html.twig")
     */
    public function childrenAction(Tag $tag)
    {
        $children = $tag->getChildren();

        return ['children' => $children, 'parent' => $tag];
    }

    /**
     * @Route("/preview/{id}", name="pumukitnewadmin_places_children_preview")
     * @ParamConverter("tag", options={"mapping": {"id": "id"}})
     * @Template("@PumukitNewAdmin/Place/preview_data.html.twig")
     */
    public function previewAction(Tag $tag)
    {
        $multimediaObjects = $this->documentManager->getRepository(MultimediaObject::class)->findBy(['tags._id' => new ObjectId($tag->getId())]);

        $series = [];
        foreach ($multimediaObjects as $multimediaObject) {
            $series[$multimediaObject->getSeries()->getId()] = $multimediaObject->getSeries()->getTitle();
        }

        return ['tag' => $tag, 'series' => $series];
    }

    /**
     * @Route("/create/{id}", name="pumukitnewadmin_places_create")
     * @Template("@PumukitNewAdmin/Place/create.html.twig")
     *
     * @param mixed|null $id
     */
    public function createAction(Request $request, $id = null)
    {
        if ($id) {
            $parent = $this->documentManager->getRepository(Tag::class)->findOneBy(['_id' => new ObjectId($id)]);
            $isPrecinct = true;
        } else {
            $parent = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => 'PLACES']);
            $isPrecinct = false;
        }

        $suggested_code = $this->autogenerateCode($parent, $isPrecinct);

        $tag = new Tag();
        $tag->setCod($suggested_code);
        $tag->setParent($parent);

        $form = $this->createForm(TagType::class, $tag, ['translator' => $this->translator, 'locale' => $request->getLocale()]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->documentManager->persist($tag);
                $this->documentManager->flush();
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], JsonResponse::HTTP_CONFLICT);
            }

            return new JsonResponse(['success']);
        }

        return ['tag' => $tag, 'form' => $form->createView(), 'suggested_code' => $suggested_code, 'parent' => $parent];
    }

    /**
     * @Route("/delete/{id}", name="pumukitnewadmin_places_delete")
     * @ParamConverter("tag", options={"mapping": {"id": "id"}})
     */
    public function deletePlaceAction(Request $request, Tag $tag)
    {
        try {
            $this->tagService->deleteTag($tag);
            $this->documentManager->flush();

            return $this->redirectToRoute('pumukitnewadmin_places_index');
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @Route("/update/{id}", name="pumukitnewadmin_places_update")
     * @ParamConverter("tag", options={"mapping": {"id": "id"}})
     * @Template("@PumukitNewAdmin/Place/update.html.twig")
     */
    public function updateAction(Request $request, Tag $tag)
    {
        $locale = $request->getLocale();
        $form = $this->createForm(TagType::class, $tag, ['translator' => $this->translator, 'locale' => $locale]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->tagService->updateTag($tag);
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], JsonResponse::HTTP_CONFLICT);
            }

            return new JsonResponse(['success']);
        }

        return ['tag' => $tag, 'form' => $form->createView()];
    }

    private function autogenerateCode(Tag $parent, bool $isPrecinct)
    {
        $code = [];
        $delimiter = ($isPrecinct) ? 'PRECINCT' : 'PLACE';

        foreach ($parent->getChildren() as $child) {
            $tagCode = explode($delimiter, $child->getCod());
            $code[] = $tagCode[1];
        }

        if (empty($code)) {
            $suggested_code = $delimiter.'1';
        } else {
            $suggested_code = $delimiter.((int) max($code) + 1);
        }

        if ($isPrecinct) {
            $suggested_code = $parent->getCod().$suggested_code;
        }

        return $suggested_code;
    }
}
