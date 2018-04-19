<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\NewAdminBundle\Form\Type\TagType;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Security("is_granted('ROLE_ACCESS_TAGS')")
 * @Route("/places")
 */
class PlaceController extends Controller implements NewAdminController
{
    /**
     * @param Request $request
     *
     * @return array
     *
     * @Route("/", name="pumukitnewadmin_places_index")
     * @Template("PumukitNewAdminBundle:Place:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $placeTag = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => 'PLACES'));
        $places = $dm->getRepository('PumukitSchemaBundle:Tag')->findBy(array('parent.$id' => new \MongoId($placeTag->getId())), array('title.'.$request->getLocale() => 1));

        return array('places' => $places);
    }

    /**
     * @param Request $request
     *
     * @return array
     *
     * @Route("/parent/", name="pumukitnewadmin_places_parent")
     * @Template("PumukitNewAdminBundle:Place:parent_list.html.twig")
     */
    public function parentAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $placeTag = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => 'PLACES'));
        $places = $dm->getRepository('PumukitSchemaBundle:Tag')->findBy(array('parent.$id' => new \MongoId($placeTag->getId())), array('title.'.$request->getLocale() => 1));

        return array('places' => $places);
    }

    /**
     * @param Tag $tag
     *
     * @return array
     *
     * @Route("/children/{id}", name="pumukitnewadmin_places_children")
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"id": "id"}})
     * @Template("PumukitNewAdminBundle:Place:children_list.html.twig")
     */
    public function childrenAction(Tag $tag)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $tag = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('_id' => $tag->getId()));
        $children = $tag->getChildren();

        return array('children' => $children, 'parent' => $tag);
    }

    /**
     * @param Tag $tag
     *
     * @return array
     *
     * @Route("/preview/{id}", name="pumukitnewadmin_places_children_preview")
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"id": "id"}})
     * @Template("PumukitNewAdminBundle:Place:preview_data.html.twig")
     */
    public function previewAction(Tag $tag)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $multimediaObjects = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findBy(array('tags._id' => new \MongoId($tag->getId())));

        $series = array();
        foreach ($multimediaObjects as $multimediaObject) {
            $series[$multimediaObject->getSeries()->getId()] = $multimediaObject->getSeries()->getTitle();
        }

        return array('tag' => $tag, 'series' => $series);
    }

    /**
     * @param Request $request
     * @param null    $id
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/create/{id}", name="pumukitnewadmin_places_create")
     * @Template("PumukitNewAdminBundle:Place:create.html.twig")
     */
    public function createAction(Request $request, $id = null)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $translator = $this->get('translator');

        if ($id) {
            $parent = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('_id' => new \MongoId($id)));
            $isPrecinct = true;
        } else {
            $parent = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => 'PLACES'));
            $isPrecinct = false;
        }

        $suggested_code = $this->autogenerateCode($parent, $isPrecinct);

        $tag = new Tag();
        $tag->setCod($suggested_code);
        $tag->setParent($parent);

        $form = $this->createForm(new TagType($translator, $request->getLocale()), $tag);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $dm->persist($tag);
                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => $e->getMessage()), JsonResponse::HTTP_CONFLICT);
            }

            return new JsonResponse(array('success'));
        }

        return array('tag' => $tag, 'form' => $form->createView(), 'suggested_code' => $suggested_code, 'parent' => $parent);
    }

    /**
     * @param Request $request
     * @param Tag     $tag
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Exception
     *
     * @Route("/delete/{id}", name="pumukitnewadmin_places_delete")
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"id": "id"}})
     */
    public function deletePlaceAction(Request $request, Tag $tag)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $tagService = $this->get('pumukitschema.tag');

        try {
            $tagService->deleteTag($tag);
            $dm->flush();

            return $this->redirectToRoute('pumukitnewadmin_places_index');
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @param Request $request
     * @param Tag     $tag
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/update/{id}", name="pumukitnewadmin_places_update")
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"id": "id"}})
     * @Template("PumukitNewAdminBundle:Place:update.html.twig")
     */
    public function updateAction(Request $request, Tag $tag)
    {
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $form = $this->createForm(new TagType($translator, $locale), $tag);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->get('pumukitschema.tag')->updateTag($tag);
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => $e->getMessage()), JsonResponse::HTTP_CONFLICT);
            }

            return new JsonResponse(array('success'));
        }

        return array('tag' => $tag, 'form' => $form->createView());
    }

    /**
     * @param Tag  $parent
     * @param bool $isPrecinct
     *
     * @return int|string
     */
    private function autogenerateCode(Tag $parent, $isPrecinct)
    {
        $code = array();
        $delimiter = ($isPrecinct) ? 'PRECINCT' : 'PLACE';

        foreach ($parent->getChildren() as $child) {
            $tagCode = explode($delimiter, $child->getCod());
            dump($delimiter);
            dump($tagCode);
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
