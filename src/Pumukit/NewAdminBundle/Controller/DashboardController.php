<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\Live;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Repository\MultimediaObjectRepository;
use Pumukit\SchemaBundle\Services\StatsService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_DASHBOARD')")
 */
class DashboardController extends AbstractController implements NewAdminControllerInterface
{
    /** @var DocumentManager */
    protected $documentManager;

    /** @var StatsService */
    protected $statsService;

    /** @var ProfileService */
    protected $profileService;

    /** @var RouterInterface */
    private $router;

    public function __construct(
        DocumentManager $documentManager,
        StatsService $statsService,
        ProfileService $profileService,
        RouterInterface $router,
    )
    {
        $this->documentManager = $documentManager;
        $this->statsService = $statsService;
        $this->profileService = $profileService;
        $this->router = $router;
    }

    /**
     * @Route("/dashboard")
     * @Route("/dashboard/default", name="pumukit_newadmin_dashboard_index_default")
     *
     * @Template("@PumukitNewAdmin/Dashboard/index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $data = ['stats' => false];

        $groupBy = $request->get('group_by', 'year');

        $stats = $this->statsService->getGlobalStats($groupBy);

        $data['stats'] = $stats;

        $storage = $this->profileService->getDirOutInfo();
        $data['storage'] = $storage;

        $seriesRepo = $this->documentManager->getRepository(Series::class);

        $data['num_series'] = $seriesRepo->count();
        $data['num_mm'] = array_sum(array_map(function ($e) {
            return $e['num'];
        }, $stats));
        $data['duration'] = array_sum(array_map(function ($e) {
            return $e['duration'];
        }, $stats));
        $data['size'] = array_sum(array_map(function ($e) {
            return $e['size'];
        }, $stats));

        $data['num_users'] = count($this->documentManager->getRepository(User::class)->findAll());

        $data['series'] = count($this->documentManager->getRepository(Series::class)->findAll());

        $data['live'] = count($this->documentManager->getRepository(MultimediaObject::class)->findBy(['type' => MultimediaObject::TYPE_LIVE]));
        $data['channels'] = count($this->documentManager->getRepository(Live::class)->findAll());


        $data['multimedia_object_audio'] = count($this->documentManager->getRepository(MultimediaObject::class)->findBy(['type' => MultimediaObject::TYPE_AUDIO]));
        $data['multimedia_object_document'] = count($this->documentManager->getRepository(MultimediaObject::class)->findBy(['type' => MultimediaObject::TYPE_DOCUMENT]));
        $data['multimedia_object_image'] = count($this->documentManager->getRepository(MultimediaObject::class)->findBy(['type' => MultimediaObject::TYPE_IMAGE]));
        $data['multimedia_object_external'] = count($this->documentManager->getRepository(MultimediaObject::class)->findBy(['type' => MultimediaObject::TYPE_EXTERNAL]));
        $data['multimedia_object_video'] = count($this->documentManager->getRepository(MultimediaObject::class)->findBy(['type' => MultimediaObject::TYPE_VIDEO, 'status' => ['$ne' => MultimediaObject::STATUS_PROTOTYPE]]));
        $data['multimedia_object'] = $data['multimedia_object_audio'] + $data['multimedia_object_document'] + $data['multimedia_object_image'] + $data['multimedia_object_external'] +$data['multimedia_object_video'];

        return ['data' => $data];
    }
}
