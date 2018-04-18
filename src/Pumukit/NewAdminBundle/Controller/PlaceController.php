<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\NewAdminBundle\Form\Type\TagType;

/**
 * @Security("is_granted('ROLE_ACCESS_TAGS')")
 * @Route("/places")
 */
class PlaceController extends Controller implements NewAdminController
{
    /**
     * @Route("/", name="pumukitnewadmin_places_index")
     * @Template("PumukitNewAdminBundle:Place:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $placeTag = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => 'PLACES'));
        $places = $placeTag->getChildren();

        return array('places' => $places);
    }

    /**
     * @Route("/children/{id}", name="pumukitnewadmin_places_children")
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"id": "id"}})
     * @Template("PumukitNewAdminBundle:Place:children_list.html.twig")
     */
    public function childrenAction(Tag $tag)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $tag = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('_id' => $tag->getId()));
        $children = $tag->getChildren();

        return array('children' => $children);
    }

    /**
     * @Route("/preview/{id}", name="pumukitnewadmin_places_children_preview")
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"id": "id"}})
     * @Template("PumukitNewAdminBundle:Place:preview_data.html.twig")
     */
    public function previewAction(Tag $tag)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $multimediaObjects = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findBy(array('tags._id' =>  new \MongoId($tag->getId())));

        $series = array();
        foreach ($multimediaObjects as $multimediaObject) {
            $series[$multimediaObject->getSeries()->getId()] = $multimediaObject->getSeries()->getTitle();
        }

        return array('tag' => $tag, 'series' => $series);
    }

}
