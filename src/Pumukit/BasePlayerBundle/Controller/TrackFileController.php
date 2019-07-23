<?php

namespace Pumukit\BasePlayerBundle\Controller;

use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class TrackFileController.
 */
class TrackFileController extends Controller
{
    /**
     * @Route("/trackfile/{id}.{ext}", name="pumukit_trackfile_index")
     * @Route("/trackfile/{id}", name="pumukit_trackfile_index_no_ext")
     *
     * @param string  $id
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return BinaryFileResponse|Response|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexAction($id, Request $request)
    {
        if (!preg_match('/^[a-f\d]{24}$/i', $id)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        [$mmobj, $track] = $this->getMmobjAndTrack($id);

        if ($this->shouldIncreaseViews($track, $request)) {
            $this->dispatchViewEvent($mmobj, $track);
        }

        // Master without url
        if (!$track->getUrl()) {
            if ($request->query->getBoolean('forcedl')) {
                $response = new BinaryFileResponse($track->getPath());
                $response->trustXSendfileTypeHeader();
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

                return $response;
            }

            throw $this->createNotFoundException("Not mmobj found with the track id: {$id}");
        }

        if ($secret = $this->container->getParameter('pumukitplayer.secure_secret')) {
            $timestamp = time() + $this->container->getParameter('pumukitplayer.secure_duration');
            $hash = $this->getHash($track, $timestamp, $secret, $request->getClientIp());

            return $this->redirect($track->getUrl()."?md5={$hash}&expires={$timestamp}&".http_build_query($request->query->all(), '', '&'));
        }

        if ($request->query->all()) {
            return $this->redirect($track->getUrl().'?'.http_build_query($request->query->all()));
        }

        return $this->redirect($track->getUrl());
    }

    /**
     * @Route("/trackplayed/{id}", name="pumukit_trackplayed_index")
     *
     * @param Request $request
     * @param string  $id
     *
     * @throws \Exception
     *
     * @return JsonResponse
     */
    public function trackPlayedAction(Request $request, $id)
    {
        if (!preg_match('/^[a-f\d]{24}$/i', $id)) {
            return new JsonResponse(['status' => 'error']);
        }

        [$mmobj, $track] = $this->getMmobjAndTrack($id);

        if ('on_play' != $this->container->getParameter('pumukitplayer.when_dispatch_view_event')) {
            return new JsonResponse(['status' => 'error']);
        }

        if (0 !== strpos($request->headers->get('referer'), $request->getSchemeAndHttpHost())) {
            return new JsonResponse(['status' => 'error']);
        }

        $this->dispatchViewEvent($mmobj, $track);

        return new JsonResponse(['status' => 'success']);
    }

    /**
     * @param Track     $track
     * @param float|int $timestamp
     * @param string    $secret
     * @param string    $ip
     *
     * @return mixed
     */
    protected function getHash(Track $track, $timestamp, $secret, $ip)
    {
        $url = $track->getUrl();
        $path = parse_url($url, PHP_URL_PATH);

        return str_replace('=', '', strtr(base64_encode(md5("{$timestamp}{$path}{$ip} {$secret}", true)), '+/', '-_'));
    }

    /**
     * @param Track   $track
     * @param Request $request
     *
     * @return bool
     */
    protected function shouldIncreaseViews(Track $track, Request $request)
    {
        if ('on_load' != $this->container->getParameter('pumukitplayer.when_dispatch_view_event')) {
            return false;
        }

        if ($track->containsTag('presentation/delivery')) {
            return false;
        }

        $range = $request->headers->get('range');
        $start = $request->headers->get('start');
        if (!$range && !$start) {
            return true;
        }
        if ($range && 'bytes=0-' == substr($range, 0, 8)) {
            return true;
        }
        if (null !== $start && 0 == $start) {
            return true;
        }

        return false;
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param null|Track       $track
     */
    protected function dispatchViewEvent(MultimediaObject $multimediaObject, Track $track = null)
    {
        $event = new ViewedEvent($multimediaObject, $track);
        $this->get('event_dispatcher')->dispatch(BasePlayerEvents::MULTIMEDIAOBJECT_VIEW, $event);
    }

    /**
     * @param string $id
     *
     * @throws \Exception
     *
     * @return array
     */
    private function getMmobjAndTrack($id)
    {
        $mmobjRepo = $this->get('doctrine_mongodb.odm.document_manager')->getRepository(MultimediaObject::class);

        $mmobj = $mmobjRepo->findOneByTrackId($id);
        if (!$mmobj) {
            throw $this->createNotFoundException("Not mmobj found with the track id: {$id}");
        }

        $track = $mmobj->getTrackById($id);
        if ($track->isHide()) {
            $logger = $this->container->get('logger');
            $logger->warning('Trying to reproduce an hide track');
        }

        if (!$this->isGranted('play', $mmobj)) {
            throw $this->createNotFoundException("Not mmobj found with the public track id: {$id}");
        }

        return [
            $mmobj,
            $track,
        ];
    }
}
