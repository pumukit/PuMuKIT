<?php

namespace Pumukit\CoreBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SecurityController extends AbstractController
{
    /**
     * @Route("/security/edit/{id}", name="pumukit_webtv_can_edit_multimediaobject")
     * @Template("PumukitCoreBundle:Security:editButton.html.twig")
     */
    public function canEditAction(Request $request, DocumentManager $documentManager, string $id)
    {
        //Performance: No queries for anonymous users
        $request->attributes->set('noindex', true);
        if (!$this->isGranted(PermissionProfile::SCOPE_PERSONAL) && !$this->isGranted(PermissionProfile::SCOPE_GLOBAL)) {
            return ['access' => false, 'multimediaObject' => null];
        }

        $multimediaObject = $documentManager->find(MultimediaObject::class, $id);

        if (!$multimediaObject) {
            throw $this->createNotFoundException();
        }

        $canEdit = $this->isGranted('edit', $multimediaObject);
        if (!$canEdit) {
            throw new AccessDeniedException('Not enought permissions to edit');
        }

        return ['access' => $canEdit, 'multimediaObject' => $multimediaObject];
    }
}
