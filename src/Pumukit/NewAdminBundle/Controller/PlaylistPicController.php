<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\SchemaBundle\Document\Playlist;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\SeriesPicService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_EDIT_PLAYLIST')")
 */
class PlaylistPicController extends AbstractController implements NewAdminControllerInterface
{
    /** @var SeriesPicService */
    private $seriesPicService;
    /** @var DocumentManager */
    private $documentManager;
    /** @var PaginationService */
    private $paginationService;
    /** @var SessionInterface */
    private $session;

    public function __construct(
        SeriesPicService $seriesPicService,
        DocumentManager $documentManager,
        PaginationService $paginationService,
        SessionInterface $session
    ) {
        $this->seriesPicService = $seriesPicService;
        $this->documentManager = $documentManager;
        $this->paginationService = $paginationService;
        $this->session = $session;
    }

    /**
     * @Template("@PumukitNewAdmin/Pic/create.html.twig")
     */
    public function createAction(Series $playlist)
    {
        return [
            'resource' => $playlist,
            'resource_name' => 'playlist',
        ];
    }

    /**
     * @Template("@PumukitNewAdmin/Pic/list.html.twig")
     */
    public function listAction(Series $playlist)
    {
        return [
            'resource' => $playlist,
            'resource_name' => 'playlist',
        ];
    }

    /**
     * @Template("@PumukitNewAdmin/Pic/list.html.twig")
     */
    public function updateAction(Request $request, Series $playlist)
    {
        $isBanner = false;
        if (($url = $request->get('url')) || ($url = $request->get('picUrl'))) {
            $isBanner = $request->query->get('banner', false);
            $bannerTargetUrl = $request->get('url_bannerTargetUrl', null);
            $playlist = $this->seriesPicService->addPicUrl($playlist, $url, $isBanner, $bannerTargetUrl);
        }

        if ($isBanner) {
            return $this->redirectToRoute('pumukitnewadmin_playlist_update', ['id' => $playlist->getId()]);
        }

        return [
            'resource' => $playlist,
            'resource_name' => 'playlist',
        ];
    }

    /**
     * @Template("@PumukitNewAdmin/Pic/upload.html.twig")
     */
    public function uploadAction(Request $request, Series $playlist)
    {
        $isBanner = false;

        try {
            if (0 === $request->files->count() && 0 === $request->request->count()) {
                throw new \Exception('PHP ERROR: File exceeds post_max_size ('.ini_get('post_max_size').')');
            }
            if ($request->files->has('file')) {
                $isBanner = $request->query->get('banner', false);
                $bannerTargetUrl = $request->get('file_bannerTargetUrl', null);
                $this->seriesPicService->addPicFile($playlist, $request->files->get('file'), $isBanner, $bannerTargetUrl);
            }
        } catch (\Exception $e) {
            return [
                'resource' => $playlist,
                'resource_name' => 'playlist',
                'uploaded' => 'failed',
                'message' => $e->getMessage(),
                'isBanner' => $isBanner,
            ];
        }

        return [
            'resource' => $playlist,
            'resource_name' => 'playlist',
            'uploaded' => 'success',
            'message' => 'New Pic added.',
            'isBanner' => $isBanner,
        ];
    }

    public function deleteAction(Request $request)
    {
        $picId = $request->get('id');

        $repo = $this->documentManager->getRepository(Series::class);

        if (!$playlist = $repo->findByPicId($picId)) {
            throw $this->createNotFoundException('Requested playlist does not exist');
        }

        $playlist = $this->seriesPicService->removePicFromSeries($playlist, $picId);

        return $this->redirectToRoute('pumukitnewadmin_playlist_update', ['id' => $playlist->getId()]);
    }

    public function upAction(Request $request)
    {
        $picId = $request->get('id');

        $repo = $this->documentManager->getRepository(Series::class);
        $playlist = $repo->findByPicId($picId);
        if (!$playlist instanceof Series) {
            throw $this->createNotFoundException('Requested playlist does not exist');
        }

        $playlist->upPicById($picId);

        $this->documentManager->persist($playlist);
        $this->documentManager->flush();

        return $this->redirectToRoute('pumukitnewadmin_playlistpic_list', ['id' => $playlist->getId()]);
    }

    public function downAction(Request $request)
    {
        $picId = $request->get('id');

        $repo = $this->documentManager->getRepository(Series::class);

        $playlist = $repo->findByPicId($picId);
        if (!$playlist instanceof Series) {
            throw $this->createNotFoundException('Requested playlist does not exist');
        }

        $playlist->downPicById($picId);

        $this->documentManager->persist($playlist);
        $this->documentManager->flush();

        return $this->redirectToRoute('pumukitnewadmin_playlistpic_list', ['id' => $playlist->getId()]);
    }

    /**
     * @Template("@PumukitNewAdmin/Pic/picstoaddlist.html.twig")
     */
    public function picstoaddlistAction(Request $request, Series $playlist)
    {
        if ($request->get('page', null)) {
            $this->session->set('admin/playlistpic/page', $request->get('page', 1));
        }
        $page = (int) ($this->session->get('admin/playlistpic/page', 1));
        $limit = 12;

        $urlPics = $this->seriesPicService->getRecommendedPics($playlist);

        $total = (int) (ceil(count($urlPics) / $limit));

        $pics = $this->getPaginatedPics($urlPics, $limit, $page);

        return [
            'resource' => $playlist,
            'resource_name' => 'playlist',
            'pics' => $pics,
            'page' => $page,
            'total' => $total,
        ];
    }

    /**
     * @Template("@PumukitNewAdmin/Pic/banner.html.twig")
     */
    public function bannerAction(Request $request, Series $playlist)
    {
        return [
            'resource' => $playlist,
            'resource_name' => 'playlist',
        ];
    }

    private function getPaginatedPics($urlPics, $limit, $page)
    {
        return $this->paginationService->createArrayAdapter($urlPics, $page, $limit);
    }
}
