<?php

namespace Pumukit\CoreBundle\Controller;

use Pumukit\CoreBundle\Services\UploadDispatcherService;
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
    private $uploadDispatcherService;
    private $inboxUploadURL;
    private $inboxUploadLIMIT;

    public function __construct(UploadDispatcherService $uploadDispatcherService, string $inboxUploadURL, int $inboxUploadLIMIT)
    {
        $this->uploadDispatcherService = $uploadDispatcherService;
        $this->inboxUploadURL = $inboxUploadURL;
        $this->inboxUploadLIMIT = $inboxUploadLIMIT;
    }

    /**
     * @Route("/inbox", name="inbox")
     * @Template("@PumukitCore/Inbox/template.html.twig")
     */
    public function inbox(): array
    {
        return [
            'inboxUploadURL' => $this->inboxUploadURL,
            'inboxUploadLIMIT' => $this->inboxUploadLIMIT,
        ];
    }

    /**
     * @Route("/dispatchImport", name="inbox_auto_import")
     */
    public function dispatchImport(Request $request): JsonResponse
    {
        try {
            $this->uploadDispatcherService->dispatchUploadFromInbox($this->getUser(), $request->get('fileName'));
        } catch (\Exception $exception) {
            return new JsonResponse(['success' => false]);
        }

        return new JsonResponse(['success' => true]);
    }
}
