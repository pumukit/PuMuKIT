<?php

namespace Pumukit\WebTVBundle\Controller;

use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\Live;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\WebTVBundle\Form\Type\ContactType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/live/{id}", name="pumukit_live_id")
     * @Template("PumukitWebTVBundle:Live/Basic:template.html.twig")
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Live $live, Request $request)
    {
        $this->updateBreadcrumbs($live->getName(), 'pumukit_live_id', ['id' => $live->getId()]);

        return $this->doLive($live, $request, false);
    }

    /**
     * @Route("/live/iframe/{id}", name="pumukit_live_iframe_id")
     * @Template("PumukitWebTVBundle:Live/Basic:template_iframe.html.twig")
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     */
    public function iframeAction(Live $live, Request $request)
    {
        return $this->doLive($live, $request, true);
    }

    /**
     * @Route("/live/event/{id}", name="pumukit_live_event_id")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id": "id"}})
     * @Template("PumukitWebTVBundle:Live/Advance:template.html.twig")
     *
     * @throws \MongoException
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexEventAction(MultimediaObject $multimediaObject, Request $request)
    {
        $embeddedEventSessionService = $this->get('pumukitschema.eventsession');

        $criteria = [
            '_id' => new ObjectId($multimediaObject->getId()),
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
            }

            return $this->iframeEventAction($multimediaObject, $request, false);
        }

        return $this->iframeEventAction($multimediaObject, $request, false);
    }

    /**
     * @Route("/live/event/iframe/{id}", name="pumukit_live_event_iframe_id")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id": "id"}})
     * @Template("PumukitWebTVBundle:Live/Advance:iframe.html.twig")
     *
     * @param bool $iframe
     *
     * @throws \MongoException
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function iframeEventAction(MultimediaObject $multimediaObject, Request $request, $iframe = true)
    {
        if (embeddedBroadcast::TYPE_PASSWORD === $multimediaObject->getEmbeddedBroadcast()->getType() && $multimediaObject->getEmbeddedBroadcast()->getPassword() !== $request->get('broadcast_password')) {
            return $this->render($iframe ? 'PumukitWebTVBundle:Live/Basic:template_iframe_password.html.twig' : 'PumukitWebTVBundle:Live/Basic:template_password.html.twig', [
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
            '_id' => new ObjectId($multimediaObject->getId()),
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
            }
            if ($session->getStart() > $date) {
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
            'showDownloads' => true,
        ];
    }

    /**
     * @return array
     *
     * @Route("/live", name="pumukit_live")
     * @Template("PumukitWebTVBundle:Live/Basic:template.html.twig")
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

    /**
     * @Route("/live/playlist/{id}", name="pumukit_live_playlist_id", defaults={"_format": "xml"})
     * @Template("PumukitWebTVBundle:Live/Basic:playlist.xml.twig")
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
            $response['items'] = '/bundles/pumukitwebtv/live/video/default.mp4';
        }

        return $response;
    }

    /**
     * @Route("/event/contact/{id}", name="pumukit_webtv_contact_event")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id": "id"}})
     *
     * @return JsonResponse
     */
    public function contactAction(MultimediaObject $multimediaObject, Request $request)
    {
        $translator = $this->get('translator');
        if ('POST' == $request->getMethod() && $this->checkCaptcha($request->request->get('g-recaptcha-response'), $request->getClientIp())) {
            $mail = $this->container->hasParameter('pumukit_notification.sender_email') ? $this->container->getParameter('pumukit_notification.sender_email') : 'noreplay@yourplatform.es';
            $to = $multimediaObject->getEmbeddedSocial()->getEmail();

            $data = $request->request->get('pumukit_multimedia_object_contact');
            $bodyMail = sprintf(" * URL: %s\n * ".$translator->trans('Email').": %s\n * ".$translator->trans('Name').": %s\n * ".$translator->trans('Content').": %s\n ", $request->headers->get('referer', 'No referer'), $data['email'], $data['name'], $data['content']);

            $pumukitInfo = $this->container->getParameter('pumukit.info');
            $subject = sprintf(
                '%s - %s: %s',
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
        }

        return new JsonResponse([
            'success' => false,
            'message' => $translator->trans('please verify form data'),
        ]);
    }

    /**
     * @param bool $iframe
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     */
    protected function doLive(Live $live, Request $request, $iframe = true)
    {
        if ($live->getPasswd() && $live->getPasswd() !== $request->get('broadcast_password')) {
            return $this->render($iframe ? 'PumukitWebTVBundle:Live/Basic:template_iframe_password.html.twig' : 'PumukitWebTVBundle:Live/Basic:template_password.html.twig', [
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
     * @param string $title
     * @param string $routeName
     */
    protected function updateBreadcrumbs($title, $routeName, array $routeParameters = [])
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addList($title, $routeName, $routeParameters);
    }

    /**
     * @param string $seriesId
     *
     * @throws \MongoException
     *
     * @return \Doctrine\ODM\MongoDB\Query\Builder
     */
    private function getMultimediaObjects($seriesId)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $qb = $dm->getRepository(MultimediaObject::class)->createStandardQueryBuilder()
            ->field('status')->equals(MultimediaObject::STATUS_PUBLISHED)
            ->field('tags.cod')->equals('PUCHWEBTV')
            ->field('series')->equals(new ObjectId($seriesId));
        $qb->field('tracks')->elemMatch($qb->expr()->field('tags')->equals('display')->field('hide')->equals(false));

        return $qb;
    }

    /**
     * @param string|null $response $request->request->get('g-recaptcha-response')
     * @param string|null $remoteip optional $request->getClientIp()
     *
     * @return mixed
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
     * @return bool|string
     */
    private function recaptchaHttpPost($data)
    {
        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);

        return curl_exec($verify);
    }
}
