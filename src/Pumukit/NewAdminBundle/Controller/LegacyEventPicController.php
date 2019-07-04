<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Pumukit\LiveBundle\Document\Event;

/**
 * @Security("is_granted('ROLE_ACCESS_LIVE_EVENTS')")
 */
class LegacyEventPicController extends Controller implements NewAdminControllerInterface
{
    /**
     * @Template("PumukitNewAdminBundle:Pic:create.html.twig")
     */
    public function createAction(Event $event, Request $request)
    {
        return [
            'resource' => $event,
            'resource_name' => 'event',
        ];
    }

    /**
     * Assign a picture from an url.
     */
    public function updateAction(Event $event, Request $request)
    {
        if ($url = $request->get('url')) {
            $picService = $this->get('pumukitlive.legacyeventpic');
            $picService->addPicUrl($event, $url);
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_event_list'));
    }

    /**
     * @Template("PumukitNewAdminBundle:Pic:upload_event.html.twig")
     */
    public function uploadAction(Event $event, Request $request)
    {
        try {
            if (0 === $request->files->count() && 0 === $request->request->count()) {
                throw new \Exception('PHP ERROR: File exceeds post_max_size ('.ini_get('post_max_size').')');
            }
            if ($request->files->has('file')) {
                $picService = $this->get('pumukitlive.legacyeventpic');
                $picService->addPicFile($event, $request->files->get('file'));
            }
        } catch (\Exception $e) {
            return [
                'event' => $event,
                'uploaded' => 'failed',
                'message' => $e->getMessage(),
            ];
        }

        return [
            'event' => $event,
            'uploaded' => 'success',
            'message' => 'New Pic added.',
        ];
    }

    /**
     * Delete pic.
     */
    public function deleteAction(Event $event, Request $request)
    {
        $this->get('pumukitlive.legacyeventpic')->removePicFromEvent($event);

        return $this->redirect($this->generateUrl('pumukitnewadmin_event_list'));
    }
}
