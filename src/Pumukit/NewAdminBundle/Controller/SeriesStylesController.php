<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesStyle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SeriesStylesController.
 *
 * @Route ("/series/styles")
 * @Security("is_granted('ROLE_ACCESS_SERIES_STYLE')")
 */
class SeriesStylesController extends Controller
{
    /**
     * @Route("/", name="pumukit_newadmin_series_styles")
     * @Template("PumukitNewAdminBundle:SeriesStyle:crud.html.twig")
     */
    public function menuAction()
    {
        return [];
    }

    /**
     * @Route("/list", name="pumukit_newadmin_series_styles_list")
     * @Template("PumukitNewAdminBundle:SeriesStyle:list.html.twig")
     */
    public function listAction()
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $styles = $dm->getRepository(SeriesStyle::class)->findAll();

        usort($styles, function ($a, $b) {
            return strtolower($a->getName()) > strtolower($b->getName());
        });

        return ['styles' => $styles];
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

        return new JsonResponse(['success', 'id' => $style->getId()]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @Route("/edit", name="pumukit_newadmin_series_styles_edit")
     */
    public function editAction(Request $request)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $id = $request->request->get('id');
        if (isset($id)) {
            $style = $dm->getRepository(SeriesStyle::class)->findOneBy(
                ['_id' => new \MongoId($request->request->get('id'))]
            );
        } else {
            return new JsonResponse(['error']);
        }

        $style->setText($request->request->get('style_text'));
        $dm->flush();

        $session = $this->get('session');
        $session->set('seriesstyle/id', $style->getId());

        return new JsonResponse(['success']);
    }

    /**
     * @param $id
     *
     * @return JsonResponse
     *
     * @Route("/delete/{id}", name="pumukit_newadmin_series_styles_delete")
     */
    public function deleteAction($id)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $translator = $this->get('translator');
        $session = $this->get('session');

        $style = $dm->getRepository(SeriesStyle::class)->findOneBy(['_id' => new \MongoId($id)]);

        if ($style) {
            $series = $dm->getRepository(Series::class)->findOneBy(
                ['series_style' => new \MongoId($style->getId())]
            );

            if (!$series) {
                $dm->remove($style);
                $dm->flush();

                $session->set('seriesstyle/id', '');

                return new JsonResponse(['success', 'msg' => $translator->trans('Successfully deleted series style')]);
            }

            return new JsonResponse(['error', 'msg' => $translator->trans('There are series with this series style')]);
        }

        return new JsonResponse(['error', 'msg' => $translator->trans("Series style {$style->getId}() doesn't exists")]);
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
            $style = $dm->getRepository(SeriesStyle::class)->findOneBy(
                ['_id' => new \MongoId($id)]
            );
        } else {
            $style = '';
        }

        return ['style' => $style];
    }
}
