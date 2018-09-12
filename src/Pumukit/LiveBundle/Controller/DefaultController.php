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
        $this->updateBreadcrumbs($live->getName(), 'pumukit_live_id', array('id' => $live->getId()));

        return $this->iframeAction($live, $request, false);
    }

    /**
     * @param Live    $live
     * @param Request $request
     * @param bool    $iframe
     *
     * @Route("/live/iframe/{id}", name="pumukit_live_iframe_id")
     * @Template("PumukitLiveBundle:Default:iframe.html.twig")
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     */
    public function iframeAction(Live $live, Request $request, $iframe = true)
    {
        if ($live->getPasswd() && $live->getPasswd() !== $request->get('broadcast_password')) {
            return $this->render($iframe ? 'PumukitLiveBundle:Default:iframepassword.html.twig' : 'PumukitLiveBundle:Default:indexpassword.html.twig', array(
                'live' => $live,
                'invalid_password' => boolval($request->get('broadcast_password')),
            ));
        }
        $userAgent = $request->headers->get('user-agent');
        $mobileDetectorService = $this->get('mobile_detect.mobile_detector');
        $mobileDevice = ($mobileDetectorService->isMobile($userAgent) || $mobileDetectorService->isTablet($userAgent));
        $isIE = $mobileDetectorService->version('IE');
        $versionIE = $isIE ? floatval($isIE) : 11.0;

        return array(
            'live' => $live,
            'mobile_device' => $mobileDevice,
            'isIE' => $isIE,
            'versionIE' => $versionIE,
        );
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
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $embeddedEventSessionService = $this->get('pumukitschema.eventsession');

        $criteria = array(
            '_id' => new \MongoId($multimediaObject->getId()),
        );

        $nowSessions = $embeddedEventSessionService->findCurrentSessions($criteria, 0, true);
        $nextSession = $embeddedEventSessionService->findNextSessions($criteria, 0, true);

        if (count($nextSession) > 0 or count($nowSessions) > 0) {
            $translator = $this->get('translator');
            $this->updateBreadcrumbs($translator->trans('Live events'), 'pumukit_webtv_events');

            return $this->iframeEventAction($multimediaObject, $request, false);
        } else {
            $series = $multimediaObject->getSeries();
            $multimediaObjects = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->createStandardQueryBuilder()
                ->field('status')->equals(MultimediaObject::STATUS_PUBLISHED)
                ->field('tags.cod')->equals('PUCHWEBTV')
                ->field('series')->equals(new \MongoId($series->getId()))
                ->getQuery()->execute();
            if (1 == count($multimediaObjects)) {
                $multimediaObjects->next();
                $multimediaObject = $multimediaObjects->current();

                return $this->redirectToRoute('pumukit_webtv_multimediaobject_index', array('id' => $multimediaObject->getId()));
            } elseif (count($multimediaObjects) > 1) {
                if (!$series->isHide()) {
                    return $this->redirectToRoute('pumukit_webtv_series_index', array('id' => $series->getId()));
                } else {
                    return $this->iframeEventAction($multimediaObject, $request, false);
                }
            } else {
                return $this->iframeEventAction($multimediaObject, $request, false);
            }
        }
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
            return $this->render($iframe ? 'PumukitLiveBundle:Default:iframepassword.html.twig' : 'PumukitLiveBundle:Default:indexpassword.html.twig', array(
                'live' => $multimediaObject->getEmbeddedEvent(),
                'invalid_password' => boolval($request->get('broadcast_password')),
            ));
        }

        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $userAgent = $request->headers->get('user-agent');
        $mobileDetectorService = $this->get('mobile_detect.mobile_detector');
        $mobileDevice = ($mobileDetectorService->isMobile($userAgent) || $mobileDetectorService->isTablet($userAgent));
        $isIE = $mobileDetectorService->version('IE');
        $versionIE = $isIE ? floatval($isIE) : 11.0;

        $translator = $this->get('translator');
        $locale = $request->getLocale();

        $form = $this->createForm(new ContactType($translator, $locale));

        $activeContact = false;
        $captchaPublicKey = '';
        if ($this->container->hasParameter('liveevent_contact_and_share') and $this->container->getParameter('liveevent_contact_and_share')) {
            $captchaPublicKey = $this->container->getParameter('captcha_public_key');
            $activeContact = true;
        }

        $embeddedEventSessionService = $this->get('pumukitschema.eventsession');

        $criteria = array(
            '_id' => new \MongoId($multimediaObject->getId()),
        );

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
            if ($session->getStart() < $date and $session->getEnds() > $date) {
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

        return array(
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
        );
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
        $repo = $this->get('doctrine_mongodb.odm.document_manager')->getRepository('PumukitLiveBundle:Live');
        $live = $repo->findOneBy(array());

        if (!$live) {
            throw $this->createNotFoundException('The live channel does not exist');
        }

        $this->updateBreadcrumbs($live->getName(), 'pumukit_live', array('id' => $live->getId()));

        return $this->iframeAction($live, $request, false);
    }

    protected function updateBreadcrumbs($title, $routeName, array $routeParameters = array())
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
        $intro = $this->container->hasParameter('pumukit2.intro') ? $this->container->getParameter('pumukit2.intro') : null;
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $mmobjsPlaylist = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findBy(array('properties.is_live_playlist' => true));

        $response = array('live' => $live);
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

            $pumukit2info = $this->container->getParameter('pumukit2.info');
            $subject = sprintf('%s - %s: %s',
                $pumukit2info['title'],
                $translator->trans('New contact from live event'),
                $multimediaObject->getEmbeddedEvent()->getName()
            );

            $message = \Swift_Message::newInstance();
            $message->setSubject($subject)->setSender($mail)->setFrom($mail)->setTo($to)->setBody($bodyMail, 'text/plain');
            $sent = $this->get('mailer')->send($message);

            if (0 == $sent) {
                $this->get('logger')->error('Event contact: Error sending message from - '.$request->request->get('email'));
            }

            return new JsonResponse(array(
                'success' => true,
                'message' => $translator->trans('email send'),
            ));
        } else {
            return new JsonResponse(array(
                'success' => false,
                'message' => $translator->trans('please verify form data'),
            ));
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

        if (null == $response || 0 == strlen($response)) {
            return false;
        }

        $response = $this->_recaptcha_http_post(array(
            'secret' => $privatekey,
            'remoteip' => $remoteip,
            'response' => $response,
        ));

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
    private function _recaptcha_http_post($data)
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
