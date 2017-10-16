<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\SchemaBundle\Document\SeriesStyle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SeriesStylesController.
 *
 *
 * @Route ("/series/styles")
 * @Security("is_granted('ROLE_ADMIN')")
 */
class SeriesStylesController extends Controller
{
    /**
     * @Route("/", name="pumukit_newadmin_series_styles")
     * @Template("PumukitNewAdminBundle:SeriesStyle:crud.html.twig")

     */
    public function menuAction()
    {
        return array();
    }

    /**
     * @Route("/list", name="pumukit_newadmin_series_styles_list")
     * @Template("PumukitNewAdminBundle:SeriesStyle:list.html.twig")
     */
    public function listAction()
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $styles = $dm->getRepository('PumukitSchemaBundle:SeriesStyle')->findAll();

        return array('styles' => $styles);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @Route("/create", name="pumukit_newadmin_series_styles_create")
     */
    public function createAction(Request $request)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $style = new SeriesStyle();
        $style->setName($request->query->get('name'));
        $style->setText('');
        $dm->persist($style);
        $dm->flush();

        $session = $this->get('session');
        $session->set('seriesstyle/id', $style->getId());

        return new JsonResponse(array('success', 'id' => $style->getId()));
    }

    /**
     * @Route("/edit", name="pumukit_newadmin_series_styles_edit")
     */
    public function editAction(Request $request)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $id = $request->request->get('id');
        if (isset($id)) {
            $style = $dm->getRepository('PumukitSchemaBundle:SeriesStyle')->findOneBy(
                array('_id' => new \MongoId($request->request->get('id')))
            );
        } else {
            return new JsonResponse(array('error'));
        }

        $style->setText($request->request->get('style_text'));
        $dm->flush();

        $session = $this->get('session');
        $session->set('seriesstyle/id', $style->getId());

        return new JsonResponse(array('success'));
    }

    /**
     * @Route("/delete/{id}", name="pumukit_newadmin_series_styles_delete")
     */
    public function deleteAction($id)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $style = $dm->getRepository('PumukitSchemaBundle:SeriesStyle')->findOneBy(array('_id' => new \MongoId($id)));

        $dm->remove($style);
        $dm->flush();

        $session = $this->get('session');
        $session->set('seriesstyle/id', '');

        return $this->redirectToRoute('pumukit_newadmin_series_styles');
    }

    /**
     * @param $id
     *
     * @return array
     *
     * @Route("/show/{id}", name="pumukit_newadmin_series_styles_show")
     * @Template("PumukitNewAdminBundle:SeriesStyle:show.html.twig")
     */
    public function showAction($id = null)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        if (isset($id)) {
            $style = $dm->getRepository('PumukitSchemaBundle:SeriesStyle')->findOneBy(
                array('_id' => new \MongoId($id))
            );
        } else {
            $style = '';
        }

        return array('style' => $style);
    }
}
