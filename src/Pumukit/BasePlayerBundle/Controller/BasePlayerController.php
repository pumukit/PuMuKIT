<?php

namespace Pumukit\BasePlayerBundle\Controller;

use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class BasePlayerController extends Controller
{
    /**
     * @Route("/videoplayer/{id}", name="pumukit_videoplayer_index" )
     * @Template()
     */
    public function indexAction(MultimediaObject $multimediaObject, Request $request)
    {
        
    }

    protected function testBroadcast(MultimediaObject $multimediaObject, Request $request)
    {
        $broadcast = $multimediaObject->getBroadcast();
        if (!$broadcast) {
            return true; //No broadcast
        }

        if (Broadcast::BROADCAST_TYPE_PUB === $broadcast->getBroadcastTypeId()) {
            return true;
        }

        //TODO Add new type.
        if (('Private (LDAP)' == $broadcast->getName()) &&
            (Broadcast::BROADCAST_TYPE_COR === $broadcast->getBroadcastTypeId())) {
            if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
                return true;
            }

            if ($request->query->get('force-auth')) {
                throw $this->createAccessDeniedException('Unable to access this page!');
            }
            return new Response($this->renderView('PumukitWebTVBundle:Index:403forbidden.html.twig', array('show_forceauth' => true)), 403);
        }

        if (($broadcast->getName() == $request->headers->get('PHP_AUTH_USER', false)) &&
            ($request->headers->get('PHP_AUTH_PW') == $broadcast->getPasswd())) {
            return true;
        }

        if (Broadcast::BROADCAST_TYPE_PRI === $broadcast->getBroadcastTypeId()) {
            return new Response($this->renderView('PumukitWebTVBundle:Index:403forbidden.html.twig', array()), 403);
        }

        $seriesUrl = $this->generateUrl('pumukit_webtv_series_index', array('id' => $multimediaObject->getSeries()->getId()), true);
        $redReq = new RedirectResponse($seriesUrl, 302);

        return new Response($redReq->getContent(), 401, array('WWW-Authenticate' => 'Basic realm="Resource not public."'));
    }

    protected function dispatchViewEvent(MultimediaObject $multimediaObject, Track $track = null)
    {
        $event = new ViewedEvent($multimediaObject, $track);
        $this->get('event_dispatcher')->dispatch(BasePlayerEvents::MULTIMEDIAOBJECT_VIEW, $event);
    }

    protected function updateBreadcrumbs(MultimediaObject $multimediaObject)
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addMultimediaObject($multimediaObject);
    }

    protected function getIntro($queryIntro = false)
    {
        $hasIntro = $this->container->hasParameter('pumukit2.intro');

        if ($queryIntro && filter_var($queryIntro, FILTER_VALIDATE_URL)) {
            $intro = $queryIntro;
        } elseif ($hasIntro) {
            $intro = $this->container->getParameter('pumukit2.intro');
        } else {
            $intro = false;
        }

        return $intro;
    }
}
