<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Controller;

use Pumukit\CoreBundle\Services\InboxService;
use Pumukit\CoreBundle\Services\UploadDispatcherService;
use Pumukit\CoreBundle\Utils\FileSystemUtils;
use Pumukit\CoreBundle\Utils\FinderUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security("is_granted('ROLE_UPLOAD_INBOX')")
 */
class InboxController extends AbstractController
{
    private $inboxService;
    private $uploadDispatcherService;

    public function __construct(InboxService $inboxService, UploadDispatcherService $uploadDispatcherService)
    {
        $this->inboxService = $inboxService;
        $this->uploadDispatcherService = $uploadDispatcherService;
    }

    /**
     * @Route("/inbox", name="inbox")
     *
     * @Template("@PumukitCore/Upload/uppy_folder.html.twig")
     */
    public function inbox(): array
    {
        $inboxPath = $this->inboxService->inboxPath();
        $folders = FinderUtils::getDirectoriesFromPath($inboxPath);

        return [
            'folders' => $folders,
        ];
    }

    /**
     * @Route("/upload", name="file_upload")
     *
     * @Template("@PumukitCore/Upload/uppy_drag_and_drop.html.twig")
     */
    public function folder(Request $request): array
    {
        $formData = $request->get('inbox_form_data', []);
        $inboxUploadURL = $this->inboxService->inboxUploadURL();
        $inboxUploadLIMIT = $this->inboxService->inboxUploadLIMIT();
        $minFileSize = $this->inboxService->minFileSize();
        $maxFileSize = $this->inboxService->maxFileSize();
        $maxNumberOfFiles = $this->inboxService->maxNumberOfFiles();
        $inboxPath = $this->inboxService->inboxPath();

        $folder = trim($formData['folder']);
        $urlUpload = $inboxPath.'/'.$formData['folder'];

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

    /**
     * @Route("/check_folder", name="check_folder_before_creating")
     */
    public function checkFolderBeforeCreating(Request $request): JsonResponse
    {
        $folderName = $request->get('folder');

        if (false !== strpos($folderName, '#')) {
            return new JsonResponse(false);
        }

        return new JsonResponse($folderName);
    }

    /**
     * @Route("/dispatchImport", name="inbox_auto_import")
     */
    public function dispatchImport(Request $request): JsonResponse
    {
        try {
            $this->uploadDispatcherService->dispatchUploadFromInbox(
                $this->getUser(),
                $request->get('fileName'),
                $request->get('folder')
            );
        } catch (\Exception $exception) {
            return new JsonResponse(['success' => false]);
        }

        return new JsonResponse(['success' => true]);
    }
}
