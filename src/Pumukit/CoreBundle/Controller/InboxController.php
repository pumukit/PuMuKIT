<?php

namespace Pumukit\CoreBundle\Controller;

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
    /**
     * @Route("/inbox", name="inbox")
     * @Template("@PumukitCore/Upload/uppy_folder.html.twig")
     */
    public function inbox(): array
    {
        $inboxPath = $this->getInboxServiceConfiguration()->inboxPath();
        $folders = FinderUtils::getDirectoriesFromPath($inboxPath);

        return [
            'folders' => $folders,
        ];
    }

    /**
     * @Route("/upload", name="file_upload")
     * @Template("@PumukitCore/Upload/uppy_drag_and_drop.html.twig")
     */
    public function folder(Request $request): array
    {
        $formData = $request->get('inbox_form_data', []);
        $inboxUploadURL = $this->getInboxServiceConfiguration()->inboxUploadURL();
        $inboxUploadLIMIT = $this->getInboxServiceConfiguration()->inboxUploadLIMIT();
        $minFileSize = $this->getInboxServiceConfiguration()->minFileSize();
        $maxFileSize = $this->getInboxServiceConfiguration()->maxFileSize();
        $maxNumberOfFiles = $this->getInboxServiceConfiguration()->maxNumberOfFiles();
        $inboxPath = $this->getInboxServiceConfiguration()->inboxPath();

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

    /**
     * Create folder if not exist.
     */
    public function checkFolderAndCreateIfNotExist(string $folder): bool
    {
        $inboxPath = $this->getInboxServiceConfiguration()->inboxPath();
        $userFolder = $folder;
        $folder = $inboxPath.'/'.$userFolder;

        return FileSystemUtils::createFolder($folder);
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
        $uploadDispatcherService = $this->get('pumukit.upload_dispatcher_service');

        try {
            $uploadDispatcherService->dispatchUploadFromInbox(
                $this->getUser(),
                $request->get('fileName'),
                $request->get('folder')
            );
        } catch (\Exception $exception) {
            return new JsonResponse(['success' => false]);
        }

        return new JsonResponse(['success' => true]);
    }

    private function getInboxServiceConfiguration()
    {
        return $this->container->get('pumukit.inbox_service_configuration');
    }
}
