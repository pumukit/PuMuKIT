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
