<?php

namespace Pumukit\WebTVBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Psr\Log\LoggerInterface;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\Live;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\EmbeddedEventSessionService;
use Pumukit\WebTVBundle\Form\Type\ContactType;
use Pumukit\WebTVBundle\Services\BreadcrumbsService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use SunCat\MobileDetectBundle\DeviceDetector\MobileDetector;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class DefaultController extends AbstractController
{
    private $documentManager;
    private $breadcrumbService;
    private $embeddedEventSessionService;
    private $translator;
    private $mobileDetectorService;
    private $logger;
    private $mailer;
    private $captchaPublicKey;
    private $captchaPrivateKey;
    private $pumukitLiveEventContactAndShare;
    private $pumukitIntro;
    private $pumukitNotificationSenderEmail;
    private $pumukitInfo;

    public function __construct(
        DocumentManager $documentManager,
        BreadcrumbsService $breadcrumbService,
        EmbeddedEventSessionService $embeddedEventSessionService,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        \Swift_Mailer $mailer,
        MobileDetector $mobileDetector,
        $captchaPublicKey,
        $captchaPrivateKey,
        $pumukitLiveEventContactAndShare,
        $pumukitIntro,
        $pumukitNotificationSenderEmail,
        $pumukitInfo
    ) {
        $this->documentManager = $documentManager;
        $this->breadcrumbService = $breadcrumbService;
        $this->embeddedEventSessionService = $embeddedEventSessionService;
        $this->translator = $translator;
        $this->mobileDetectorService = $mobileDetector;
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->captchaPublicKey = $captchaPublicKey;
        $this->captchaPrivateKey = $captchaPrivateKey;
        $this->pumukitLiveEventContactAndShare = $pumukitLiveEventContactAndShare;
        $this->pumukitIntro = $pumukitIntro;
        $this->pumukitNotificationSenderEmail = $pumukitNotificationSenderEmail;
        $this->pumukitInfo = $pumukitInfo;
    }

    /**
     * @Route("/live/{id}", name="pumukit_live_id")
     * @Template("@PumukitWebTV/Live/Basic/template.html.twig")
     */
    public function indexAction(Live $live, Request $request)
    {
        $this->updateBreadcrumbs($live->getName(), 'pumukit_live_id', ['id' => $live->getId()]);

        return $this->doLive($live, $request, false);
    }

    /**
     * @Route("/live/iframe/{id}", name="pumukit_live_iframe_id")
     * @Template("@PumukitWebTV/Live/Basic/template_iframe.html.twig")
     */
    public function iframeAction(Live $live, Request $request)
    {
        return $this->doLive($live, $request);
    }

    /**
     * @Route("/live/event/{id}", name="pumukit_live_event_id")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id": "id"}})
     * @Template("@PumukitWebTV/Live/Advance/template.html.twig")
     */
    public function indexEventAction(MultimediaObject $multimediaObject, Request $request)
    {
        $criteria = [
            '_id' => new ObjectId($multimediaObject->getId()),
        ];

        $nowSessions = $this->embeddedEventSessionService->findCurrentSessions($criteria, 0, true);
        $nextSession = $this->embeddedEventSessionService->findNextSessions($criteria, 0, true);

        if (count($nextSession) > 0 || count($nowSessions) > 0) {
            $this->updateBreadcrumbs($this->translator->trans('Live events'), 'pumukit_webtv_events');

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
     * @Template("@PumukitWebTV/Live/Advance/iframe.html.twig")
     */
    public function iframeEventAction(MultimediaObject $multimediaObject, Request $request, bool $iframe = true)
    {
        if (EmbeddedBroadcast::TYPE_PASSWORD === $multimediaObject->getEmbeddedBroadcast()->getType() && $multimediaObject->getEmbeddedBroadcast()->getPassword() !== $request->get('broadcast_password')) {
            return $this->render($iframe ? '@PumukitWebTV/Live/Basic/template_iframe_password.html.twig' : '@PumukitWebTV/Live/Basic/template_password.html.twig', [
                'live' => $multimediaObject->getEmbeddedEvent(),
                'invalid_password' => (bool) ($request->get('broadcast_password')),
            ]);
        }

        $userAgent = $request->headers->get('user-agent');

        $mobileDevice = ($this->mobileDetectorService->isMobile($userAgent) || $this->mobileDetectorService->isTablet($userAgent));
        $isIE = $this->mobileDetectorService->version('IE');
        $versionIE = $isIE ? (float) $isIE : 11.0;

        $locale = $request->getLocale();

        $form = $this->createForm(ContactType::class, null, ['translator' => $this->translator, 'locale' => $locale]);

        $activeContact = false;
        $captchaPublicKey = '';
        if ($this->pumukitLiveEventContactAndShare) {
            $captchaPublicKey = $this->captchaPublicKey;
            $activeContact = true;
        }

        $criteria = [
            '_id' => new ObjectId($multimediaObject->getId()),
        ];

        $nowSessions = $this->embeddedEventSessionService->findCurrentSessions($criteria, 0, true);
        $now = new \DateTime();
        $firstNowSessionEnds = new \DateTime();
        $firstNowSessionEnds = $firstNowSessionEnds->getTimestamp();
        $firstNowSessionRemainingDuration = 0;
        foreach ($nowSessions as $session) {
            $firstNowSessionEnds = ($session['data'][0]['session']['start']->toDateTime()->format('U') + $session['data'][0]['session']['duration']) * 1000;
            $firstNowSessionRemainingDuration = $firstNowSessionEnds - ($now->getTimeStamp() * 1000);

            break;
        }

        $nextSessions = $this->embeddedEventSessionService->findNextSessions($criteria, 0, true);
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

        if ($iframe && 0 === count($nowSessions) && 0 === count($nextSessions)) {
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
     * @Route("/live", name="pumukit_live")
     * @Template("@PumukitWebTV/Live/Basic/template.html.twig")
     */
    public function defaultAction(Request $request)
    {
        $live = $this->documentManager->getRepository(Live::class)->findOneBy([]);

        if (!$live) {
            throw $this->createNotFoundException('The live channel does not exist');
        }

        $this->updateBreadcrumbs($live->getName(), 'pumukit_live', ['id' => $live->getId()]);

        return $this->doLive($live, $request, false);
    }

    /**
     * @Route("/live/playlist/{id}", name="pumukit_live_playlist_id", defaults={"_format": "xml"})
     * @Template("PumukitWebTVBundle:Live/Basic:playlist.xml.twig")
     */
    public function playlistAction(Live $live): array
    {
        $intro = $this->pumukitIntro ?? null;
        $mmobjsPlaylist = $this->documentManager->getRepository(MultimediaObject::class)->findBy([
            'properties.is_live_playlist' => true,
        ]);

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
     */
    public function contactAction(MultimediaObject $multimediaObject, Request $request): JsonResponse
    {
        if ('POST' === $request->getMethod() && $this->checkCaptcha($request->request->get('g-recaptcha-response'), $request->getClientIp())) {
            $mail = $this->pumukitNotificationSenderEmail ?? 'noreplay@yourplatform.es';
            $to = $multimediaObject->getEmbeddedSocial()->getEmail();

            $data = $request->request->get('pumukit_multimedia_object_contact');
            $bodyMail = sprintf(" * URL: %s\n * ".$this->translator->trans('Email').": %s\n * ".$this->translator->trans('Name').": %s\n * ".$this->translator->trans('Content').": %s\n ", $request->headers->get('referer', 'No referer'), $data['email'], $data['name'], $data['content']);

            $subject = sprintf(
                '%s - %s: %s',
                $this->pumukitInfo['title'],
                $this->translator->trans('New contact from live event'),
                $multimediaObject->getEmbeddedEvent()->getName()
            );

            $message = new Swift_Message();
            $message->setSubject($subject)->setSender($mail)->setFrom($mail)->setTo($to)->setBody($bodyMail, 'text/plain');
            $sent = $this->mailer->send($message);

            if (0 === $sent) {
                $this->logger->error('Event contact: Error sending message from - '.$request->request->get('email'));
            }

            return new JsonResponse([
                'success' => true,
                'message' => $this->translator->trans('email send'),
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => $this->translator->trans('please verify form data'),
        ]);
    }

    protected function doLive(Live $live, Request $request, bool $iframe = true)
    {
        if ($live->getPasswd() && $live->getPasswd() !== $request->get('broadcast_password')) {
            return $this->render($iframe ? '@PumukitWebTV/Live/Basic/template_iframe_password.html.twig' : '@PumukitWebTV/Live/Basic/template_password.html.twig', [
                'live' => $live,
                'invalid_password' => (bool) ($request->get('broadcast_password')),
            ]);
        }
        $userAgent = $request->headers->get('user-agent');
        $mobileDevice = ($this->mobileDetectorService->isMobile($userAgent) || $this->mobileDetectorService->isTablet($userAgent));
        $isIE = $this->mobileDetectorService->version('IE');
        $versionIE = $isIE ? (float) $isIE : 11.0;

        return [
            'live' => $live,
            'mobile_device' => $mobileDevice,
            'isIE' => $isIE,
            'versionIE' => $versionIE,
        ];
    }

    protected function updateBreadcrumbs(string $title, string $routeName, array $routeParameters = [])
    {
        $this->breadcrumbService->addList($title, $routeName, $routeParameters);
    }

    private function getMultimediaObjects($seriesId)
    {
        $qb = $this->documentManager->getRepository(MultimediaObject::class)->createStandardQueryBuilder()
            ->field('status')->equals(MultimediaObject::STATUS_PUBLISHED)
            ->field('tags.cod')->equals('PUCHWEBTV')
            ->field('series')->equals(new ObjectId($seriesId));
        $qb->field('tracks')->elemMatch($qb->expr()->field('tags')->equals('display')->field('hide')->equals(false));

        return $qb;
    }

    private function checkCaptcha($response, string $remoteip = '')
    {
        if (null === $response || 0 === strlen($response)) {
            return false;
        }

        $response = $this->recaptchaHttpPost([
            'secret' => $this->captchaPrivateKey,
            'remoteip' => $remoteip,
            'response' => $response,
        ]);

        return json_decode($response, true)->success;
    }

    /**
     * Submits an HTTP POST to a reCAPTCHA server.
     */
    private function recaptchaHttpPost(array $data)
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
