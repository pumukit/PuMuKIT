<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Controller;

use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Services\InboxService;
use Pumukit\CoreBundle\Services\UploadDispatcherService;
use Pumukit\CoreBundle\Utils\FileSystemUtils;
use Pumukit\CoreBundle\Utils\FinderUtils;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class InboxController extends AbstractController
{
    private $inboxService;
    private $uploadDispatcherService;
    private $logger;

    public function __construct(InboxService $inboxService, UploadDispatcherService $uploadDispatcherService, LoggerInterface $logger)
    {
        $this->inboxService = $inboxService;
        $this->uploadDispatcherService = $uploadDispatcherService;
        $this->logger = $logger;
    }

    #[Route('/inbox', name: 'inbox')]
    #[IsGranted('ROLE_UPLOAD_INBOX')]
    #[Template('@PumukitCore/Upload/uppy_folder.html.twig')]
    public function inbox(): array
    {
        $inboxPath = $this->inboxService->inboxPath();
        $folders = FinderUtils::directoriesFromPath($inboxPath);

        return [
            'folders' => $folders,
        ];
    }

    #[Route('/upload', name: 'file_upload')]
    #[IsGranted('ROLE_UPLOAD_INBOX')]
    #[Template('@PumukitCore/Upload/upload_drag_and_drop.html.twig')]
    public function folder(Request $request): array
    {
        $formData = $request->request->all('inbox_form_data');
        $inboxUploadURL = $this->inboxService->inboxUploadURL();
        $inboxUploadLIMIT = $this->inboxService->inboxUploadLIMIT();
        $minFileSize = $this->inboxService->minFileSize();
        $maxFileSize = $this->inboxService->maxFileSize();
        $maxNumberOfFiles = $this->inboxService->maxNumberOfFiles();
        $inboxPath = $this->inboxService->inboxPath();

        try {
            $folder = $this->inboxService->folderName(trim((string) ($formData['folder'] ?? '')));
        } catch (\InvalidArgumentException) {
            $folder = '';
        }
        $urlUpload = $inboxPath.'/'.$folder;

        if (!$formData || empty($folder) || !$this->checkFolderAndCreateIfNotExist($folder)) {
            $folder = '';
            $urlUpload = '';
        }

        return [
            'form_data' => $urlUpload,
            'folder' => $folder,
            'inboxUploadURL' => $inboxUploadURL,
            'inboxUploadLIMIT' => $inboxUploadLIMIT,
            'minFileSize' => $minFileSize,
            'maxFileSize' => $maxFileSize,
            'maxNumberOfFiles' => $maxNumberOfFiles,
        ];
    }

    public function checkFolderAndCreateIfNotExist(string $folder): bool
    {
        $inboxPath = $this->inboxService->inboxPath();
        $userFolder = $folder;
        $folder = $inboxPath.'/'.$userFolder;

        try {
            FileSystemUtils::createFolder($folder);

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    #[Route('/check_folder', name: 'check_folder_before_creating')]
    #[IsGranted('ROLE_UPLOAD_INBOX')]
    public function checkFolderBeforeCreating(Request $request): JsonResponse
    {
        $folderName = $request->request->getString('folder');

        if ((0 === preg_match('/^[[:alnum:]\s_]+$/', $folderName)) || empty(trim($folderName))) {
            return new JsonResponse(false);
        }

        return new JsonResponse($folderName);
    }

    #[Route('/dispatchImport', name: 'inbox_auto_import', methods: ['POST'])]
    #[IsGranted('ROLE_UPLOAD_INBOX')]
    public function dispatchImport(Request $request): JsonResponse
    {
        try {
            $this->uploadDispatcherService->dispatchUploadFromInbox(
                $this->getUser(),
                (string) $request->request->get('fileName'),
                (string) $request->request->get('series'),
                (string) $request->request->get('profile', 'master_copy')
            );
        } catch (\Exception $exception) {
            $this->logger->error('The uploaded file could not be imported: {message}', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
                'fileName' => (string) $request->request->get('fileName'),
                'series' => (string) $request->request->get('series'),
            ]);

            return new JsonResponse(['success' => false]);
        }

        return new JsonResponse(['success' => true]);
    }
}
