<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Series;

/**
 * @Security("is_granted('ROLE_ACCESS_EDIT_PLAYLIST')")
 */
class PlaylistPicController extends Controller implements NewAdminControllerInterface
{
    /**
     * @Template("PumukitNewAdminBundle:Pic:create.html.twig")
     */
    public function createAction(Series $playlist, Request $request)
    {
        return [
            'resource' => $playlist,
            'resource_name' => 'playlist',
        ];
    }

    /**
     * @Template("PumukitNewAdminBundle:Pic:list.html.twig")
     */
    public function listAction(Series $playlist)
    {
        return [
            'resource' => $playlist,
            'resource_name' => 'playlist',
        ];
    }

    /**
     * Assign a picture from an url or from an existing one to the playlist.
     *
     * @Template("PumukitNewAdminBundle:Pic:list.html.twig")
     */
    public function updateAction(Series $playlist, Request $request)
    {
        $isBanner = false;
        if (($url = $request->get('url')) || ($url = $request->get('picUrl'))) {
            $picService = $this->get('pumukitschema.seriespic');
            $isBanner = $request->query->get('banner', false);
            $bannerTargetUrl = $request->get('url_bannerTargetUrl', null);
            $playlist = $picService->addPicUrl($playlist, $url, $isBanner, $bannerTargetUrl);
        }

        if ($isBanner) {
            return $this->redirect($this->generateUrl('pumukitnewadmin_playlist_update', ['id' => $playlist->getId()]));
        }

        return [
            'resource' => $playlist,
            'resource_name' => 'playlist',
        ];
    }

    /**
     * @Template("PumukitNewAdminBundle:Pic:upload.html.twig")
     */
    public function uploadAction(Series $playlist, Request $request)
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
                $picService->addPicFile($playlist, $request->files->get('file'), $isBanner, $bannerTargetUrl);
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

    /**
     * Delete pic.
     */
    public function deleteAction(Request $request)
    {
        $picId = $request->get('id');

        $repo = $this->get('doctrine_mongodb')
              ->getRepository(Series::class);

        if (!$playlist = $repo->findByPicId($picId)) {
            throw $this->createNotFoundException('Requested playlist does not exist');
        }

        $playlist = $this->get('pumukitschema.seriespic')->removePicFromSeries($playlist, $picId);

        return $this->redirect($this->generateUrl('pumukitnewadmin_playlist_update', ['id' => $playlist->getId()]));
    }

    /**
     * Up pic.
     */
    public function upAction(Request $request)
    {
        $picId = $request->get('id');

        $repo = $this->get('doctrine_mongodb')
              ->getRepository(Series::class);

        if (!$playlist = $repo->findByPicId($picId)) {
            throw $this->createNotFoundException('Requested playlist does not exist');
        }

        $playlist->upPicById($picId);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($playlist);
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_playlistpic_list', ['id' => $playlist->getId()]));
    }

    /**
     * Down pic.
     */
    public function downAction(Request $request)
    {
        $picId = $request->get('id');

        $repo = $this->get('doctrine_mongodb')
              ->getRepository(Series::class);

        if (!$playlist = $repo->findByPicId($picId)) {
            throw $this->createNotFoundException('Requested playlist does not exist');
        }

        $playlist->downPicById($picId);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($playlist);
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_playlistpic_list', ['id' => $playlist->getId()]));
    }

    /**
     * @Template("PumukitNewAdminBundle:Pic:picstoaddlist.html.twig")
     */
    public function picstoaddlistAction(Series $playlist, Request $request)
    {
        $picService = $this->get('pumukitschema.seriespic');

        if ($request->get('page', null)) {
            $this->get('session')->set('admin/playlistpic/page', $request->get('page', 1));
        }
        $page = intval($this->get('session')->get('admin/playlistpic/page', 1));
        $limit = 12;

        $urlPics = $picService->getRecommendedPics($playlist);

        $total = intval(ceil(count($urlPics) / $limit));

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
     * @Template("PumukitNewAdminBundle:Pic:banner.html.twig")
     */
    public function bannerAction(Series $playlist, Request $request)
    {
        return [
            'resource' => $playlist,
            'resource_name' => 'playlist',
        ];
    }

    /**
     * Get paginated pics.
     *
     * @param array $urlPics
     * @param int   $limit
     * @param int   $page
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
            ->setCurrentPage($page);

        return $pics;
    }
}
