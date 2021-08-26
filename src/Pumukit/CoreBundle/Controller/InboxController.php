<?php

namespace Pumukit\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security("is_granted('ROLE_UPLOAD_INBOX')")
 */
class InboxController extends AbstractController
{
    /**
     * @Route("/inbox", name="inbox")
     * @Template("@PumukitCore/Inbox/template.html.twig")
     */
    public function inbox(): array
    {
        $inboxUploadURL = $this->container->getParameter('pumukit.inboxUploadURL');
        $inboxUploadLIMIT = $this->container->getParameter('pumukit.inboxUploadLIMIT');

        return [
            'inboxUploadURL' => $inboxUploadURL,
            'inboxUploadLIMIT' => $inboxUploadLIMIT,
        ];
    }
}
