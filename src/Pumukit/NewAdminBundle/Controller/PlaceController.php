<?php

namespace Pumukit\NewAdminBundle\Controller;

use MongoDB\BSON\ObjectId;
use Pumukit\NewAdminBundle\Form\Type\TagType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security("is_granted('ROLE_ACCESS_TAGS')")
 * @Route("/places")
 */
class PlaceController extends AbstractController implements NewAdminControllerInterface
{
    /**
     * @return array
     *
     * @Route("/", name="pumukitnewadmin_places_index")
     * @Template("PumukitNewAdminBundle:Place:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $placeTag = $dm->getRepository(Tag::class)->findOneBy(['cod' => 'PLACES']);
        $places = $dm->getRepository(Tag::class)->findBy(['parent.$id' => new ObjectId($placeTag->getId())], ['title.'.$request->getLocale() => 1]);

        return ['places' => $places];
    }

    /**
     * @return array
     *
     * @Route("/parent/", name="pumukitnewadmin_places_parent")
     * @Template("PumukitNewAdminBundle:Place:parent_list.html.twig")
     */
    public function parentAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $placeTag = $dm->getRepository(Tag::class)->findOneBy(['cod' => 'PLACES']);
        $places = $dm->getRepository(Tag::class)->findBy(['parent.$id' => new ObjectId($placeTag->getId())], ['title.'.$request->getLocale() => 1]);

        return ['places' => $places];
    }

    /**
     * @return array
     *
     * @Route("/children/{id}", name="pumukitnewadmin_places_children")
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"id": "id"}})
     * @Template("PumukitNewAdminBundle:Place:children_list.html.twig")
     */
    public function childrenAction(Tag $tag)
    {
        $children = $tag->getChildren();

        return ['children' => $children, 'parent' => $tag];
    }

    /**
     * @return array
     *
     * @Route("/preview/{id}", name="pumukitnewadmin_places_children_preview")
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"id": "id"}})
     * @Template("PumukitNewAdminBundle:Place:preview_data.html.twig")
     */
    public function previewAction(Tag $tag)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $multimediaObjects = $dm->getRepository(MultimediaObject::class)->findBy(['tags._id' => new ObjectId($tag->getId())]);

        $series = [];
        foreach ($multimediaObjects as $multimediaObject) {
            $series[$multimediaObject->getSeries()->getId()] = $multimediaObject->getSeries()->getTitle();
        }

        return ['tag' => $tag, 'series' => $series];
    }

    /**
     * @param string|null $id
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/create/{id}", name="pumukitnewadmin_places_create")
     * @Template("PumukitNewAdminBundle:Place:create.html.twig")
     */
    public function createAction(Request $request, $id = null)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        if ($id) {
            $parent = $dm->getRepository(Tag::class)->findOneBy(['_id' => new ObjectId($id)]);
            $isPrecinct = true;
        } else {
            $parent = $dm->getRepository(Tag::class)->findOneBy(['cod' => 'PLACES']);
            $isPrecinct = false;
        }

        $suggested_code = $this->autogenerateCode($parent, $isPrecinct);

        $tag = new Tag();
        $tag->setCod($suggested_code);
        $tag->setParent($parent);

        $form = $this->createForm(TagType::class, $tag, ['translator' => $this->translationService, 'locale' => $request->getLocale()]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $dm->persist($tag);
                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], JsonResponse::HTTP_CONFLICT);
            }

            return new JsonResponse(['success']);
        }

        return ['tag' => $tag, 'form' => $form->createView(), 'suggested_code' => $suggested_code, 'parent' => $parent];
    }

    /**
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/delete/{id}", name="pumukitnewadmin_places_delete")
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"id": "id"}})
     */
    public function deletePlaceAction(Request $request, Tag $tag)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        try {
            $this->tagService->deleteTag($tag);
            $dm->flush();

            return $this->redirectToRoute('pumukitnewadmin_places_index');
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/update/{id}", name="pumukitnewadmin_places_update")
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"id": "id"}})
     * @Template("PumukitNewAdminBundle:Place:update.html.twig")
     */
    public function updateAction(Request $request, Tag $tag)
    {
        $locale = $request->getLocale();
        $form = $this->createForm(TagType::class, $tag, ['translator' => $this->translationService, 'locale' => $locale]);

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

    /**
     * @param bool $isPrecinct
     *
     * @return int|string
     */
    private function autogenerateCode(Tag $parent, $isPrecinct)
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
