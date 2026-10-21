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

        $session = $request->getSession();
        $hash = '';
        $username = '';
        $email = '';

        if ($session->has('tus_sso_username') && $session->has('tus_sso_email')) {
            $username = $session->get('tus_sso_username');
            $email = $session->get('tus_sso_email');
        }

        return $this->render('@PumukitWizard/Upload/template.html.twig', [
            'series' => $series,
            'inboxUploadURL' => $this->inboxService->inboxUploadURL(),
            'hash' => $hash,
            'username' => $username,
            'email' => $email,
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
        $relativeFilePath = $request->get('filePath');
        $profile = $request->get('profile');

        try {
            $inboxBasePath = realpath($this->inboxService->inboxPath());
            if (!$inboxBasePath) {
                return new JsonResponse('Inbox not configured', 400);
            }

            $absolutePath = '' !== (string) $relativeFilePath
                ? realpath($inboxBasePath.DIRECTORY_SEPARATOR.$relativeFilePath)
                : $inboxBasePath;

            if (!$absolutePath || !str_starts_with($absolutePath.DIRECTORY_SEPARATOR, $inboxBasePath.DIRECTORY_SEPARATOR)) {
                return new JsonResponse('Invalid path', 403);
            }

            if (is_file($absolutePath)) {
                $this->uploadDispatcherService->dispatchUploadFromServer(
                    $this->getUser(),
                    $absolutePath,
                    $series,
                    $profile
                );
            } else {
                $finder = FinderUtils::filesFromPath($absolutePath);

                foreach ($finder->files() as $file) {
                    $this->uploadDispatcherService->dispatchUploadFromServer(
                        $this->getUser(),
                        $file->getPathname(),
                        $series,
                        $profile
                    );
                }
            }
        } catch (\Exception $exception) {
            return new JsonResponse($exception->getMessage(), 500);
        }

        return new JsonResponse('OK', 200);
    }
}
