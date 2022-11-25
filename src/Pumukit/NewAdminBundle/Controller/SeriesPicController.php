<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\SeriesPicService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class SeriesPicController extends AbstractController implements NewAdminControllerInterface
{
    /** @var DocumentManager */
    private $documentManager;
    /** @var SeriesPicService */
    private $seriesPicService;
    /** @var PaginationService */
    private $paginationService;
    /** @var SessionInterface */
    private $session;

    public function __construct(
        DocumentManager $documentManager,
        SeriesPicService $seriesPicService,
        PaginationService $paginationService,
        SessionInterface $session
    ) {
        $this->documentManager = $documentManager;
        $this->seriesPicService = $seriesPicService;
        $this->paginationService = $paginationService;
        $this->session = $session;
    }

    /**
     * @Template("@PumukitNewAdmin/Pic/create.html.twig")
     */
    public function createAction(Series $series)
    {
        return [
            'resource' => $series,
            'resource_name' => 'series',
        ];
    }

    /**
     * @Template("@PumukitNewAdmin/Pic/list.html.twig")
     */
    public function listAction(Series $series)
    {
        return [
            'resource' => $series,
            'resource_name' => 'series',
        ];
    }

    /**
     * @Template("@PumukitNewAdmin/Pic/list.html.twig")
     */
    public function updateAction(Series $series, Request $request)
    {
        $isBanner = false;
        if (($url = $request->get('url')) || ($url = $request->get('picUrl'))) {
            $isBanner = $request->query->get('banner', false);
            $bannerTargetUrl = $request->get('url_bannerTargetUrl', null);
            $series = $this->seriesPicService->addPicUrl($series, $url, $isBanner, $bannerTargetUrl);
        }

        if ($isBanner) {
            return $this->redirectToRoute('pumukitnewadmin_series_update', ['id' => $series->getId()]);
        }

        return [
            'resource' => $series,
            'resource_name' => 'series',
        ];
    }

    /**
     * @Template("@PumukitNewAdmin/Pic/upload.html.twig")
     */
    public function uploadAction(Series $series, Request $request)
    {
        $isBanner = false;

        try {
            if (0 === $request->files->count() && 0 === $request->request->count()) {
                throw new \Exception('PHP ERROR: File exceeds post_max_size ('.ini_get('post_max_size').')');
            }
            if ($request->files->has('file')) {
                $picService = $this->seriesPicService;
                $isBanner = $request->query->get('banner', false);
                $bannerTargetUrl = $request->get('file_bannerTargetUrl', null);
                $this->seriesPicService->addPicFile($series, $request->files->get('file'), $isBanner, $bannerTargetUrl);
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

    public function deleteAction(Request $request)
    {
        $picId = $request->get('id');

        $repo = $this->documentManager->getRepository(Series::class);

        if (!$series = $repo->findByPicId($picId)) {
            throw $this->createNotFoundException('Requested series does not exist');
        }

        $series = $this->seriesPicService->removePicFromSeries($series, $picId);

        return $this->redirectToRoute('pumukitnewadmin_series_update', ['id' => $series->getId()]);
    }

    public function upAction(Request $request)
    {
        $picId = $request->get('id');

        $repo = $this->documentManager->getRepository(Series::class);
        $series = $repo->findByPicId($picId);

        if (!$series instanceof Series) {
            throw $this->createNotFoundException('Requested series does not exist');
        }

        $series->upPicById($picId);

        $this->documentManager->persist($series);
        $this->documentManager->flush();

        return $this->redirectToRoute('pumukitnewadmin_seriespic_list', ['id' => $series->getId()]);
    }

    public function downAction(Request $request)
    {
        $picId = $request->get('id');

        $repo = $this->documentManager->getRepository(Series::class);

        $series = $repo->findByPicId($picId);
        if (!$series instanceof Series) {
            throw $this->createNotFoundException('Requested series does not exist');
        }

        $series->downPicById($picId);

        $this->documentManager->persist($series);
        $this->documentManager->flush();

        return $this->redirectToRoute('pumukitnewadmin_seriespic_list', ['id' => $series->getId()]);
    }

    /**
     * @Template("@PumukitNewAdmin/Pic/picstoaddlist.html.twig")
     */
    public function picstoaddlistAction(Request $request, Series $series)
    {
        if ($request->get('page', null)) {
            $this->session->set('admin/seriespic/page', $request->get('page', 1));
        }
        $page = (int) ($this->session->get('admin/seriespic/page', 1));
        $limit = 12;

        $urlPics = $this->seriesPicService->getRecommendedPics($series);

        $total = (int) (ceil((is_countable($urlPics) ? count($urlPics) : 0) / $limit));

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
     * @Template("@PumukitNewAdmin/Pic/banner.html.twig")
     */
    public function bannerAction(Series $series)
    {
        return [
            'resource' => $series,
            'resource_name' => 'series',
        ];
    }

    private function getPaginatedPics($urlPics, $limit, $page)
    {
        return $this->paginationService->createArrayAdapter($urlPics, $page, $limit);
    }
}
