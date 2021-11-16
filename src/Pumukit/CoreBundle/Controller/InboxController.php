<?php

namespace Pumukit\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Pumukit\CoreBundle\Utils\FinderUtils;

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
        $inboxPath = $this->container->getParameter('pumukit.inbox');
        $folders = FinderUtils::getDirectoriesFromPath($inboxPath);

        return [
            'folders' => $folders,
        ];
    }

    /**
     * @Route("/upload", name="file_upload")
     * @Template("@PumukitCore/Upload/uppy_drag_and_drop.html.twig")
     */
    public function folder(Request $request)
    {
        $formData = $request->get('inbox_form_data', []);

        if (!$formData) {
            return $this->redirect($this->generateUrl('inbox'));
        }

        $inboxUploadURL = $this->container->getParameter('pumukit.inboxUploadURL');
        $inboxUploadLIMIT = $this->container->getParameter('pumukit.inboxUploadLIMIT');
        $inboxPath = $this->container->getParameter('pumukit.inbox');
        $this->checkFolderAndCreateIfNotExist($formData['folder']);
        
        return [
            'form_data' => $inboxPath."/".$formData['folder'],
            'folder' => $formData['folder'],
            'inboxUploadURL' => $inboxUploadURL,
            'inboxUploadLIMIT' => $inboxUploadLIMIT,
        ];
    }

    /**
     * Create folder if not exixt.
     */
    public function checkFolderAndCreateIfNotExist(string $folder)
    {
        $inboxPath = $this->container->getParameter('pumukit.inbox');
        $uploadDispatcherService = $this->get('pumukit.upload_dispatcher_service');
        $userFolder = $folder;
        $folder = $inboxPath."/".$userFolder;

        $uploadDispatcherService->createFolderIfNotExist($folder);
    }

    /**
     * @Route("/check_folder", name="check_folder_before_creating")
     */
    public function checkFolderBeforeCreating(Request $request)
    {
        $folderName = $request->get('folder');
        $uploadDispatcherService = $this->get('pumukit.upload_dispatcher_service');

        if (!preg_match('/^[\w]+$/', $folderName)) {
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
            $uploadDispatcherService->dispatchUploadFromInbox($this->getUser(), $request->get('fileName'), $request->get('folder'));
        } catch (\Exception $exception) {
            return new JsonResponse(['success' => false]);
        }

        return new JsonResponse(['success' => true]);
    }
}
