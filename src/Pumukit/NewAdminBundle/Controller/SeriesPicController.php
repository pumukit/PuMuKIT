<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Series;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class SeriesPicController extends Controller implements NewAdminControllerInterface
{
    /**
     * @Template("PumukitNewAdminBundle:Pic:create.html.twig")
     *
     * @param Series  $series
     * @param Request $request
     *
     * @return array
     */
    public function createAction(Series $series, Request $request)
    {
        return [
            'resource' => $series,
            'resource_name' => 'series',
        ];
    }

    /**
     * @Template("PumukitNewAdminBundle:Pic:list.html.twig")
     *
     * @param Series $series
     *
     * @return array
     */
    public function listAction(Series $series)
    {
        return [
            'resource' => $series,
            'resource_name' => 'series',
        ];
    }

    /**
     * Assign a picture from an url or from an existing one to the series.
     *
     * @Template("PumukitNewAdminBundle:Pic:list.html.twig")
     *
     * @param Series  $series
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function updateAction(Series $series, Request $request)
    {
        $isBanner = false;
        if (($url = $request->get('url')) || ($url = $request->get('picUrl'))) {
            $picService = $this->get('pumukitschema.seriespic');
            $isBanner = $request->query->get('banner', false);
            $bannerTargetUrl = $request->get('url_bannerTargetUrl', null);
            $series = $picService->addPicUrl($series, $url, $isBanner, $bannerTargetUrl);
        }

        if ($isBanner) {
            return $this->redirect($this->generateUrl('pumukitnewadmin_series_update', ['id' => $series->getId()]));
        }

        return [
            'resource' => $series,
            'resource_name' => 'series',
        ];
    }

    /**
     * @Template("PumukitNewAdminBundle:Pic:upload.html.twig")
     *
     * @param Series  $series
     * @param Request $request
     *
     * @return array
     */
    public function uploadAction(Series $series, Request $request)
    {
        $isBanner = false;

        try {
            if (0 === $request->files->count() && 0 === $request->request->count()) {
                throw new \Exception('PHP ERROR: File exceeds post_max_size ('.ini_get('post_max_size').')');
            }
            if ($request->files->has('file')) {
                $picService = $this->get('pumukitschema.seriespic');
                $isBanner = $request->query->get('banner', false);
                $bannerTargetUrl = $request->get('file_bannerTargetUrl', null);
                $picService->addPicFile($series, $request->files->get('file'), $isBanner, $bannerTargetUrl);
            }
        } catch (\Exception $e) {
            return [
                'resource' => $series,
                'resource_name' => 'series',
                'uploaded' => 'failed',
                'message' => $e->getMessage(),
                'isBanner' => $isBanner,
            ];
        }

        return [
            'resource' => $series,
            'resource_name' => 'series',
            'uploaded' => 'success',
            'message' => 'New Pic added.',
            'isBanner' => $isBanner,
        ];
    }

    /**
     * Delete pic.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request)
    {
        $picId = $request->get('id');

        $repo = $this->get('doctrine_mongodb')
            ->getRepository(Series::class)
        ;

        if (!$series = $repo->findByPicId($picId)) {
            throw $this->createNotFoundException('Requested series does not exist');
        }

        $series = $this->get('pumukitschema.seriespic')->removePicFromSeries($series, $picId);

        return $this->redirect($this->generateUrl('pumukitnewadmin_series_update', ['id' => $series->getId()]));
    }

    /**
     * Up pic.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function upAction(Request $request)
    {
        $picId = $request->get('id');

        $repo = $this->get('doctrine_mongodb')
            ->getRepository(Series::class)
        ;

        if (!$series = $repo->findByPicId($picId)) {
            throw $this->createNotFoundException('Requested series does not exist');
        }

        $series->upPicById($picId);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($series);
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_seriespic_list', ['id' => $series->getId()]));
    }

    /**
     * Down pic.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function downAction(Request $request)
    {
        $picId = $request->get('id');

        $repo = $this->get('doctrine_mongodb')
            ->getRepository(Series::class)
        ;

        if (!$series = $repo->findByPicId($picId)) {
            throw $this->createNotFoundException('Requested series does not exist');
        }

        $series->downPicById($picId);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($series);
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_seriespic_list', ['id' => $series->getId()]));
    }

    /**
     * @Template("PumukitNewAdminBundle:Pic:picstoaddlist.html.twig")
     *
     * @param Series  $series
     * @param Request $request
     *
     * @return array
     */
    public function picstoaddlistAction(Series $series, Request $request)
    {
        $picService = $this->get('pumukitschema.seriespic');

        if ($request->get('page', null)) {
            $this->get('session')->set('admin/seriespic/page', $request->get('page', 1));
        }
        $page = (int) ($this->get('session')->get('admin/seriespic/page', 1));
        $limit = 12;

        $urlPics = $picService->getRecommendedPics($series);

        $total = (int) (ceil(count($urlPics) / $limit));

        $pics = $this->getPaginatedPics($urlPics, $limit, $page);

        return [
            'resource' => $series,
            'resource_name' => 'series',
            'pics' => $pics,
            'page' => $page,
            'total' => $total,
        ];
    }

    /**
     * @Template("PumukitNewAdminBundle:Pic:banner.html.twig")
     *
     * @param Series  $series
     * @param Request $request
     *
     * @return array
     */
    public function bannerAction(Series $series, Request $request)
    {
        return [
            'resource' => $series,
            'resource_name' => 'series',
        ];
    }

    /**
     * Get paginated pics.
     *
     * @param \Doctrine\Common\Collections\Collection $urlPics
     * @param int                                     $limit
     * @param int                                     $page
     *
     * @return Pagerfanta
     */
    private function getPaginatedPics($urlPics, $limit, $page)
    {
        $adapter = new ArrayAdapter($urlPics->toArray());
        $pics = new Pagerfanta($adapter);

        $pics
            ->setMaxPerPage($limit)
            ->setNormalizeOutOfRangePages(true)
            ->setCurrentPage($page)
        ;

        return $pics;
    }
}
