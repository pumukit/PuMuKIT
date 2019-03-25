<?php

namespace Pumukit\LegacyWebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class SecurityController.
 */
class SecurityController extends Controller
{
    /**
     * @param Request          $request
     * @param MultimediaObject $multimediaObject
     *
     * @return array
     *
     * @Route("/security/edit/{id}", name="pumukit_webtv_can_edit_multimediaobject")
     * @Template("PumukitLegacyWebTVBundle:Security:editButton.html.twig")
     */
    public function canEditAction(Request $request, $id)
    {
        //Performance: No queries for anonymous users
        $request->attributes->set('noindex', true);
        if (!$this->isGranted(PermissionProfile::SCOPE_PERSONAL) && !$this->isGranted(PermissionProfile::SCOPE_GLOBAL)) {
            return array('access' => false, 'multimediaObject' => null);
        }

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $multimediaObject = $dm->find('PumukitSchemaBundle:MultimediaObject', $id);

        if (!$multimediaObject) {
            throw $this->createNotFoundException();
        }

        $canEdit = $this->isGranted('edit', $multimediaObject);
        if (!$canEdit) {
            throw new AccessDeniedException('Not enought permissions to edit');
        }

        return array('access' => $canEdit, 'multimediaObject' => $multimediaObject);
    }
}
