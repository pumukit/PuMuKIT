<?php

namespace Pumukit\OpencastBundle\Controller;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        if (!isset($mediapackage['mediapackage']['id'])) {
            $this->get('logger')->warning('No mediapackage ID, ERROR 400 returned');

            return new Response('No mediapackage ID', 400);
        }

        $opencastImportService = $this->get('pumukit_opencast.import');
        $opencastImportService->importRecordingFromMediaPackage($mediapackage['mediapackage']);

        return new Response('Success', 200);
    }

    /**
     * @Route("/sync_tracks/{id}", name="pumukit_opencast_import_sync_tracks")
     */
    public function syncTracksAction(MultimediaObject $multimediaObject, Request $request)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $opencastImportService = $this->get('pumukit_opencast.import');
        $opencastImportService->syncTracks($multimediaObject);

        $dm->persist($multimediaObject);
        $dm->flush();

        return new Response('Success '.$multimediaObject->getTitle(), 200);
    }
}
