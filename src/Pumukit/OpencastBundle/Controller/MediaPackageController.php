<?php

namespace Pumukit\OpencastBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * @Route("/admin")
 * @Security("is_granted('ROLE_ACCESS_IMPORTER')")
 */
class MediaPackageController extends Controller
{
    /**
     * @Route("/opencast/mediapackage", name="pumukitopencast")
     * @Template("PumukitOpencastBundle:MediaPackage:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        if (!$this->container->getParameter('pumukit_opencast.show_importer_tab')) {
            throw new AccessDeniedException('Not allowed. Configure your OpencastBundle to show the Importer Tab.');
        }

        if (!$this->has('pumukit_opencast.client')) {
            throw $this->createNotFoundException('PumukitOpencastBundle not configured.');
        }

        $opencastClient = $this->get('pumukit_opencast.client');
        $repository_multimediaobjects = $this->get('doctrine_mongodb')->getRepository(MultimediaObject::class);

        $limit = 10;
        $page = $request->get('page', 1);
        $criteria = $this->getCriteria($request);

        try {
            list($total, $mediaPackages) = $opencastClient->getMediaPackages(
                (isset($criteria['name'])) ? $criteria['name']->regex : '',
                $limit,
                ($page - 1) * $limit);
        } catch (\Exception $e) {
            return new Response($this->renderView('PumukitOpencastBundle:MediaPackage:error.html.twig', ['admin_url' => $opencastClient->getUrl(), 'message' => $e->getMessage()]), 503);
        }

        $currentPageOpencastIds = [];

        $opencastService = $this->get('pumukit_opencast.job');
        $pics = [];
        foreach ($mediaPackages as $mediaPackage) {
            $currentPageOpencastIds[] = $mediaPackage['id'];
            $pics[$mediaPackage['id']] = $opencastService->getMediaPackageThumbnail($mediaPackage);
        }

        $adapter = new FixedAdapter($total, $mediaPackages);
        $pagerfanta = new Pagerfanta($adapter);

        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        $repo = $repository_multimediaobjects->createQueryBuilder()
          ->field('properties.opencast')->exists(true)
          ->field('properties.opencast')->in($currentPageOpencastIds)
          ->getQuery()
          ->execute();

        return ['mediaPackages' => $pagerfanta, 'multimediaObjects' => $repo, 'player' => $opencastClient->getPlayerUrl(), 'pics' => $pics];
    }

    /**
     * @Route("/opencast/mediapackage/{id}", name="pumukitopencast_import")
     */
    public function importAction($id, Request $request)
    {
        if (!$this->container->getParameter('pumukit_opencast.show_importer_tab')) {
            throw new AccessDeniedException('Not allowed. Configure your OpencastBundle to show the Importer Tab.');
        }

        $opencastService = $this->get('pumukit_opencast.import');
        $opencastService->importRecording($id, $request->get('invert'), $this->getUser());

        if ($request->headers->get('referer')) {
            return $this->redirect($request->headers->get('referer'));
        } else {
            return $this->redirectToRoute('pumukitopencast');
        }
    }

    /**
     * Gets the criteria values.
     */
    public function getCriteria($request)
    {
        $criteria = $request->get('criteria', []);

        if (array_key_exists('reset', $criteria)) {
            $this->get('session')->remove('admin/opencast/criteria');
        } elseif ($criteria) {
            $this->get('session')->set('admin/opencast/criteria', $criteria);
        }
        $criteria = $this->get('session')->get('admin/opencast/criteria', []);

        $new_criteria = [];

        foreach ($criteria as $property => $value) {
            //preg_match('/^\/.*?\/[imxlsu]*$/i', $e)
            if ('' !== $value) {
                $new_criteria[$property] = new \MongoRegex('/'.$value.'/i');
            }
        }

        return $new_criteria;
    }
}
