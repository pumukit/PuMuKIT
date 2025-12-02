<?php

namespace App\Dashboard\UI\Backoffice\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\Live;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\StatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class ViewDashboardController extends AbstractController
{
    public function __construct(
        protected StatsService $statsService,
        protected DocumentManager $documentManager,
        protected ProfileService $profileService
    ) {}

    public function __invoke(Request $request)
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
        $data['multimedia_object'] = $data['multimedia_object_audio'] + $data['multimedia_object_document'] + $data['multimedia_object_image'] + $data['multimedia_object_external'] + $data['multimedia_object_video'];

        return $this->render('@Dashboard/UI/Backoffice/Views/dashboard.html.twig', ['data' => $data]);
    }
}
