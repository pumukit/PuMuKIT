<?php

namespace Pumukit\PodcastBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class ModalController extends Controller
{
    /**
     * @Route("/admin/podcast/model/mm/{id}", name="pumukitpodcast_modal_index", defaults={"filter": false})
     * @Template("PumukitPodcastBundle:Modal:index.html.twig")
     */
    public function indexAction(Request $request, MultimediaObject $mm)
    {
        return ['mm' => $mm];
    }
}
