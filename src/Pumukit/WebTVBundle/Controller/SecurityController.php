<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class SecurityController extends Controller implements WebTVController
{
    /**
     * @param Request          $request
     * @param MultimediaObject $multimediaObject
     *
     * @return array
     *
     * @Route("/security/edit/{id}", name="pumukit_webtv_can_edit_multimediaobject", defaults={"show_hide": true})
     * @Template("PumukitWebTVBundle:Security:editButton.html.twig")
     */
    public function canEditAction(Request $request, MultimediaObject $multimediaObject)
    {
        $canEdit = $this->isGranted('edit', $multimediaObject);

        return array('access' => $canEdit, 'multimediaObject' => $multimediaObject);
    }
}
