<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\SchemaBundle\Document\Event;
use Pumukit\SchemaBundle\Services\LegacyEventPicService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Security("is_granted('ROLE_ACCESS_LIVE_EVENTS')")
 */
class LegacyEventPicController extends AbstractController implements NewAdminControllerInterface
{
    /** @var LegacyEventPicService */
    private $legacyEventPicService;

    public function __construct(LegacyEventPicService $legacyEventPicService)
    {
        $this->legacyEventPicService = $legacyEventPicService;
    }

    /**
     * @Template("PumukitNewAdminBundle:Pic:create.html.twig")
     */
    public function createAction(Request $request, Event $event)
    {
        return [
            'resource' => $event,
            'resource_name' => 'event',
        ];
    }

    /**
     * Assign a picture from an url.
     */
    public function updateAction(Request $request, Event $event)
    {
        if ($url = $request->get('url')) {
            $this->legacyEventPicService->addPicUrl($event, $url);
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
                $this->legacyEventPicService->addPicFile($event, $request->files->get('file'));
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

    public function deleteAction(Request $request, Event $event)
    {
        $this->legacyEventPicService->removePicFromEvent($event);

        return $this->redirect($this->generateUrl('pumukitnewadmin_event_list'));
    }
}
