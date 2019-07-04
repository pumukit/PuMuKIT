<?php

namespace Pumukit\LiveBundle\Controller;

use Pumukit\WebTVBundle\Form\Type\ContactType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Pumukit\LiveBundle\Document\Live;

class DefaultController extends Controller
{
    /**
     * @param Live    $live
     * @param Request $request
     *
     * @Route("/live/{id}", name="pumukit_live_id")
     * @Template("PumukitLiveBundle:Default:index.html.twig")
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Live $live, Request $request)
    {
        $this->updateBreadcrumbs($live->getName(), 'pumukit_live_id', ['id' => $live->getId()]);

        return $this->doLive($live, $request, false);
    }

    /**
     * @param Live    $live
     * @param Request $request
     *
     * @Route("/live/iframe/{id}", name="pumukit_live_iframe_id")
     * @Template("PumukitLiveBundle:Default:iframe.html.twig")
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     */
    public function iframeAction(Live $live, Request $request)
    {
        return $this->doLive($live, $request, true);
    }

    protected function doLive(Live $live, Request $request, $iframe = true)
    {
        if ($live->getPasswd() && $live->getPasswd() !== $request->get('broadcast_password')) {
            return $this->render($iframe ? 'PumukitLiveBundle:Default:iframepassword.html.twig' : 'PumukitLiveBundle:Default:indexpassword.html.twig', [
                'live' => $live,
                'invalid_password' => (bool) ($request->get('broadcast_password')),
            ]);
        }
        $userAgent = $request->headers->get('user-agent');
        $mobileDetectorService = $this->get('mobile_detect.mobile_detector');
        $mobileDevice = ($mobileDetectorService->isMobile($userAgent) || $mobileDetectorService->isTablet($userAgent));
        $isIE = $mobileDetectorService->version('IE');
        $versionIE = $isIE ? (float) $isIE : 11.0;

        return [
            'live' => $live,
            'mobile_device' => $mobileDevice,
            'isIE' => $isIE,
            'versionIE' => $versionIE,
        ];
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     *
     * @Route("/live/event/{id}", name="pumukit_live_event_id")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id": "id"}})
     * @Template("PumukitLiveBundle:Advance:index.html.twig")
     */
    public function indexEventAction(MultimediaObject $multimediaObject, Request $request)
    {
        $embeddedEventSessionService = $this->get('pumukitschema.eventsession');

        $criteria = [
            '_id' => new \MongoId($multimediaObject->getId()),
        ];

        $nowSessions = $embeddedEventSessionService->findCurrentSessions($criteria, 0, true);
        $nextSession = $embeddedEventSessionService->findNextSessions($criteria, 0, true);

        if (count($nextSession) > 0 || count($nowSessions) > 0) {
            $translator = $this->get('translator');
            $this->updateBreadcrumbs($translator->trans('Live events'), 'pumukit_webtv_events');

            return $this->iframeEventAction($multimediaObject, $request, false);
        }

        $series = $multimediaObject->getSeries();

        $qb = $this->getMultimediaObjects($series->getId());
        $multimediaObjects = $qb->getQuery()->execute();

        if (1 === count($multimediaObjects)) {
            $multimediaObjects->next();
            $mm = $multimediaObjects->current();

            if ($mm->getDisplayTrack()) {
                return $this->redirectToRoute('pumukit_webtv_multimediaobject_index', ['id' => $mm->getId()]);
            }
        } elseif (count($multimediaObjects) > 1) {
            if (!$series->isHide()) {
                return $this->redirectToRoute('pumukit_webtv_series_index', ['id' => $series->getId()]);
            } else {
                return $this->iframeEventAction($multimediaObject, $request, false);
            }
        }

        return $this->iframeEventAction($multimediaObject, $request, false);
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     * @param bool             $iframe
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     *
     * @Route("/live/event/iframe/{id}", name="pumukit_live_event_iframe_id")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id": "id"}})
     * @Template("PumukitLiveBundle:Advance:iframe.html.twig")
     */
    public function iframeEventAction(MultimediaObject $multimediaObject, Request $request, $iframe = true)
    {
        if (embeddedBroadcast::TYPE_PASSWORD === $multimediaObject->getEmbeddedBroadcast()->getType() && $multimediaObject->getEmbeddedBroadcast()->getPassword() !== $request->get('broadcast_password')) {
            return $this->render($iframe ? 'PumukitLiveBundle:Default:iframepassword.html.twig' : 'PumukitLiveBundle:Default:indexpassword.html.twig', [
                'live' => $multimediaObject->getEmbeddedEvent(),
                'invalid_password' => (bool) ($request->get('broadcast_password')),
            ]);
        }

        $userAgent = $request->headers->get('user-agent');
        $mobileDetectorService = $this->get('mobile_detect.mobile_detector');
        $mobileDevice = ($mobileDetectorService->isMobile($userAgent) || $mobileDetectorService->isTablet($userAgent));
        $isIE = $mobileDetectorService->version('IE');
        $versionIE = $isIE ? (float) $isIE : 11.0;

        $translator = $this->get('translator');
        $locale = $request->getLocale();

        $form = $this->createForm(ContactType::class, null, ['translator' => $translator, 'locale' => $locale]);

        $activeContact = false;
        $captchaPublicKey = '';
        if ($this->container->hasParameter('liveevent_contact_and_share') && $this->container->getParameter('liveevent_contact_and_share')) {
            $captchaPublicKey = $this->container->getParameter('captcha_public_key');
            $activeContact = true;
        }

        $embeddedEventSessionService = $this->get('pumukitschema.eventsession');

        $criteria = [
            '_id' => new \MongoId($multimediaObject->getId()),
        ];

        $nowSessions = $embeddedEventSessionService->findCurrentSessions($criteria, 0, true);
        $now = new \DateTime();
        $firstNowSessionEnds = new \DateTime();
        $firstNowSessionEnds = $firstNowSessionEnds->getTimestamp();
        $firstNowSessionRemainingDuration = 0;
        foreach ($nowSessions as $session) {
            $firstNowSessionEnds = ($session['data'][0]['session']['start']->sec + $session['data'][0]['session']['duration']) * 1000;
            $firstNowSessionRemainingDuration = $firstNowSessionEnds - ($now->getTimeStamp() * 1000);
            break;
        }

        $nextSessions = $embeddedEventSessionService->findNextSessions($criteria, 0, true);
        $date = new \DateTime();
        $firstNextSession = '';
        foreach ($multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession() as $session) {
            if ($session->getStart() < $date && $session->getEnds() > $date) {
                $firstNextSession = $session->getStart()->getTimestamp() * 1000;
                break;
            } elseif ($session->getStart() > $date) {
                $firstNextSession = $session->getStart()->getTimestamp() * 1000;
                break;
            }
        }

        $secondsToEvent = null;
        if (!empty($firstNextSession)) {
            $secondsToEvent = $firstNextSession - ($now->getTimeStamp() * 1000);
        }

        if (0 === count($nowSessions) && 0 === count($nextSessions) && $iframe) {
            $qb = $this->getMultimediaObjects($multimediaObject->getSeries()->getId());
            $qb->field('embeddedBroadcast.type')->equals(EmbeddedBroadcast::TYPE_PUBLIC);
            $multimediaObjectPlaylist = $qb->getQuery()->execute()->getSingleResult();

            if ($multimediaObjectPlaylist) {
                $autostart = $request->query->get('autostart', 'true');

                return $this->redirectToRoute(
                    'pumukit_playlistplayer_index',
                    ['id' => $multimediaObjectPlaylist->getSeries()->getId(), 'autostart' => $autostart]
                );
            }
        }

        return [
            'multimediaObject' => $multimediaObject,
            'firstNextSession' => $firstNextSession,
            'secondsToEvent' => $secondsToEvent,
            'firstNowSessionEnds' => $firstNowSessionEnds,
            'firstNowSessionDuration' => $firstNowSessionRemainingDuration,
            'nowSessions' => $nowSessions,
            'nextSessions' => $nextSessions,
            'captcha_public_key' => $captchaPublicKey,
            'live' => $multimediaObject->getEmbeddedEvent()->getLive(),
            'contact' => $form->createView(),
            'activeContact' => $activeContact,
            'success' => -1,
            'mobile_device' => $mobileDevice,
            'isIE' => $isIE,
            'versionIE' => $versionIE,
        ];
    }

    /**
     * @param $seriesId
     *
     * @return mixed
     */
    private function getMultimediaObjects($seriesId)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $qb = $dm->getRepository(MultimediaObject::class)->createStandardQueryBuilder()
            ->field('status')->equals(MultimediaObject::STATUS_PUBLISHED)
            ->field('tags.cod')->equals('PUCHWEBTV')
            ->field('series')->equals(new \MongoId($seriesId));
        $qb->field('tracks')->elemMatch($qb->expr()->field('tags')->equals('display')->field('hide')->equals(false));

        return $qb;
    }

    /**
     * @param Request $request
     *
     * @return array
     *
     * @Route("/live", name="pumukit_live")
     * @Template("PumukitLiveBundle:Default:index.html.twig")
     */
    public function defaultAction(Request $request)
    {
        $repo = $this->get('doctrine_mongodb.odm.document_manager')->getRepository(Live::class);
        $live = $repo->findOneBy([]);

        if (!$live) {
            throw $this->createNotFoundException('The live channel does not exist');
        }

        $this->updateBreadcrumbs($live->getName(), 'pumukit_live', ['id' => $live->getId()]);

        return $this->doLive($live, $request, false);
    }

    protected function updateBreadcrumbs($title, $routeName, array $routeParameters = [])
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addList($title, $routeName, $routeParameters);
    }

    /**
     * @param Live $live
     *
     * @Route("/live/playlist/{id}", name="pumukit_live_playlist_id", defaults={"_format": "xml"})
     * @Template("PumukitLiveBundle:Default:playlist.xml.twig")
     *
     * @return array
     */
    public function playlistAction(Live $live)
    {
        $intro = $this->container->hasParameter('pumukit.intro') ? $this->container->getParameter('pumukit.intro') : null;
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $mmobjsPlaylist = $dm->getRepository(MultimediaObject::class)->findBy(['properties.is_live_playlist' => true]);

        $response = ['live' => $live];
        if ($mmobjsPlaylist) {
            $response['items'] = $mmobjsPlaylist;
        } elseif ($intro) {
            $response['items'] = $intro;
        } else {
            $response['items'] = '/bundles/pumukitlive/video/default.mp4';
        }

        return $response;
    }

    /**
     * @param Request          $request
     * @param MultimediaObject $multimediaObject
     *
     * @return JsonResponse
     *
     * @Route("/event/contact/{id}", name="pumukit_webtv_contact_event")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id": "id"}})
     */
    public function contactAction($multimediaObject, Request $request)
    {
        $translator = $this->get('translator');
        if ('POST' == $request->getMethod() && $this->checkCaptcha($request->request->get('g-recaptcha-response'), $request->getClientIp())) {
            $mail = $this->container->hasParameter('pumukit_notification.sender_email') ? $this->container->getParameter('pumukit_notification.sender_email') : 'noreplay@yourplatform.es';
            $to = $multimediaObject->getEmbeddedSocial()->getEmail();

            $data = $request->request->get('pumukit_multimedia_object_contact');
            $bodyMail = sprintf(" * URL: %s\n * ".$translator->trans('Email').": %s\n * ".$translator->trans('Name').": %s\n * ".$translator->trans('Content').": %s\n ", $request->headers->get('referer', 'No referer'), $data['email'], $data['name'], $data['content']);

            $pumukitInfo = $this->container->getParameter('pumukit.info');
            $subject = sprintf('%s - %s: %s',
                $pumukitInfo['title'],
                $translator->trans('New contact from live event'),
                $multimediaObject->getEmbeddedEvent()->getName()
            );

            $message = \Swift_Message::newInstance();
            $message->setSubject($subject)->setSender($mail)->setFrom($mail)->setTo($to)->setBody($bodyMail, 'text/plain');
            $sent = $this->get('mailer')->send($message);

            if (0 == $sent) {
                $this->get('logger')->error('Event contact: Error sending message from - '.$request->request->get('email'));
            }

            return new JsonResponse([
                'success' => true,
                'message' => $translator->trans('email send'),
            ]);
        } else {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('please verify form data'),
            ]);
        }
    }

    /**
     * @param string $response $request->request->get('g-recaptcha-response')
     * @param string $remoteip optional $request->getClientIp()
     *
     * @return jsonResponse | boolean
     */
    private function checkCaptcha($response, $remoteip = '')
    {
        $privatekey = $this->container->getParameter('captcha_private_key');

        if (null === $response || 0 == strlen($response)) {
            return false;
        }

        $response = $this->recaptchaHttpPost([
            'secret' => $privatekey,
            'remoteip' => $remoteip,
            'response' => $response,
        ]);

        $res = json_decode($response);

        return $res->success;
    }

    /**
     * Submits an HTTP POST to a reCAPTCHA server.
     *
     * @param array $data
     *
     * @return array response
     */
    private function recaptchaHttpPost($data)
    {
        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($verify);

        return $response;
    }
}
