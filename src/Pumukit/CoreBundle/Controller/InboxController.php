<?php

namespace Pumukit\CoreBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Security("is_granted('ROLE_UPLOAD_INBOX')")
 */
class InboxController extends AbstractController
{
    private $inboxUploadURL;
    private $inboxUploadLIMIT;

    public function __construct(string $inboxUploadURL, string $inboxUploadLIMIT)
    {
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
            'inboxUploadLIMIT' => $this->inboxUploadLIMIT
        ];
    }
}
