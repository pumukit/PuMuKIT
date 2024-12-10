<?php

declare(strict_types=1);

namespace Pumukit\WizardBundle\Controller;

use Pumukit\CoreBundle\Services\InboxService;
use Pumukit\CoreBundle\Services\UploadDispatcherService;
use Pumukit\CoreBundle\Utils\FinderUtils;
use Pumukit\SchemaBundle\Services\Repository\SeriesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
    public function upload(Request $request, string $series): Response
    {
        $series = $this->seriesRepository->search($series);

        return $this->render('@PumukitWizard/Upload/template.html.twig', [
            'series' => $series,
            'inboxUploadURL' => $this->inboxService->inboxUploadURL(),
            'inboxUploadLIMIT' => $this->inboxService->inboxUploadLIMIT(),
            'minFileSize' => $this->inboxService->minFileSize(),
            'maxFileSize' => $this->inboxService->maxFileSize(),
            'maxNumberOfFiles' => $this->inboxService->maxNumberOfFiles(),
            'show_profiles' => null !== $request->query->get('show_profiles') ? filter_var($request->query->get('show_profiles'), FILTER_VALIDATE_BOOLEAN) : true,
            'profile' => $request->query->get('profile', null),
        ]);
    }

    /**
     * @Route("/{series}/server/upload", name="wizard_upload_from_server")
     */
    public function uploadFromServer(Request $request, string $series): JsonResponse
    {
        $path = $request->get('filePath');
        $profile = $request->get('profile');

        try {
            $finder = FinderUtils::filesFromPath($path);
            foreach ($finder->files() as $file) {
                $this->uploadDispatcherService->dispatchUploadFromServer(
                    $this->getUser(),
                    $file->getPathname(),
                    $series,
                    $profile
                );
            }
        } catch (\Exception $exception) {
            return new JsonResponse($exception->getMessage(), 500);
        }

        return new JsonResponse('OK', 200);
    }
}
