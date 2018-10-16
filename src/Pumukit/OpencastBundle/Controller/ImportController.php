<?php

namespace Pumukit\OpencastBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/api/opencast")
 */
class ImportController extends Controller
{
    /**
     * @Route("/import_event", name="pumukit_opencast_import_event")
     */
    public function eventAction(Request $request)
    {
        $mediapackage = json_decode($request->request->get('mediapackage'), true);
        $this->get('logger')->info(json_encode($mediapackage));

        if (!isset($mediapackage['mediapackage']['id'])) {
            $this->get('logger')->warning('No mediapackage ID, ERROR 400 returned');

            return new Response('No mediapackage ID', 400);
        }

        $mediapackageId = $mediapackage['mediapackage']['id'];
        $opencastImportService = $this->get('pumukit_opencast.import');
        $opencastImportService->importRecording($mediapackageId);

        return new Response('Success', 200);
    }
}
