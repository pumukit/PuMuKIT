<?php

declare(strict_types=1);

namespace Pumukit\WizardBundle\Controller;

use Pumukit\CoreBundle\Services\InboxService;
use Pumukit\CoreBundle\Services\UploadDispatcherService;
use Pumukit\SchemaBundle\Services\Repository\SeriesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/wizard")
 */
final class WizardController extends AbstractController
{
    private InboxService $inboxService;
    private UploadDispatcherService $uploadDispatcherService;
    private SeriesRepository $seriesRepository;

    public function __construct(InboxService $inboxService, UploadDispatcherService $uploadDispatcherService, SeriesRepository $seriesRepository)
    {
        $this->inboxService = $inboxService;
        $this->uploadDispatcherService = $uploadDispatcherService;
        $this->seriesRepository = $seriesRepository;
    }

    /**
     * @Route("/{series}/upload", name="wizard_upload")
     */
    public function upload(string $series): Response
    {
        $series = $this->seriesRepository->search($series);

        return $this->render('@PumukitWizard/Upload/template.html.twig', [
            'series' => $series,
            'inboxUploadURL' => $this->inboxService->inboxUploadURL(),
            'inboxUploadLIMIT' => $this->inboxService->inboxUploadLIMIT(),
            'minFileSize' => $this->inboxService->minFileSize(),
            'maxFileSize' => $this->inboxService->maxFileSize(),
            'maxNumberOfFiles' => $this->inboxService->maxNumberOfFiles(),
        ]);
    }
}
