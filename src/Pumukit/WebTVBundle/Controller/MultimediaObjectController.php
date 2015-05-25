<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Broadcast;
use Symfony\Component\Security\Core\SecurityContext;

class MultimediaObjectController extends Controller
{
    /**
     * @Route("/video/{id}", name="pumukit_webtv_multimediaobject_index")
     * @Template("PumukitWebTVBundle:MultimediaObject:index.html.twig")
     */
    public function indexAction(MultimediaObject $multimediaObject, Request $request)
    {
      if (($broadcast = $multimediaObject->getBroadcast()) && 
          (Broadcast::BROADCAST_TYPE_PUB !== $broadcast->getBroadcastTypeId())) {
          //TODO SSO CAS for old Opencast videos
          //throw $this->createNotFoundException();
          return $this->redirect($this->generateUrl("pumukit_webtv_multimediaobject_auth_broadcast", array('id' => $multimediaObject->getId())));
      }

      $response = $this->preExecute($multimediaObject);
      if($response instanceof Response) {
        return $response;
      }
    
      $track = $request->query->has('track_id') ?
        $multimediaObject->getTrackById($request->query->get('track_id')) :
        $multimediaObject->getFilteredTrackWithTags(array('display'));

      if (!$track)
        throw $this->createNotFoundException();

      $this->updateBreadcrumbs($multimediaObject);
      $this->incNumView($multimediaObject, $track);
        
      return array('autostart' => $request->query->get('autostart', 'true'),
                   'intro' => $this->getIntro($request->query->get('intro')),
                   'multimediaObject' => $multimediaObject,
                   'track' => $track);
    }

   /**
     * @Route("/iframe/{id}", name="pumukit_webtv_multimediaobject_iframe")
     * @Template()
     */
    public function iframeAction(MultimediaObject $multimediaObject, Request $request)
    {
      return $this->indexAction($multimediaObject, $request);
    }


    /**
     * @Route("/video/magic/{secret}", name="pumukit_webtv_multimediaobject_magicindex", defaults={"filter": false})
     * @Template("PumukitWebTVBundle:MultimediaObject:index.html.twig")
     */
    public function magicIndexAction(MultimediaObject $multimediaObject, Request $request)
    {
      $response = $this->preExecute($multimediaObject);
      if($response instanceof Response) {
        return $response;
      }

      $track = $request->query->has('track_id') ?
        $multimediaObject->getTrackById($request->query->get('track_id')) :
        $multimediaObject->getTrackWithTag('display');

      $this->updateBreadcrumbs($multimediaObject);
      $this->incNumView($multimediaObject, $track);

      return array('autostart' => $request->query->get('autostart', 'true'),
                   'intro' => $this->getIntro($request->query->get('intro')),
                   'multimediaObject' => $multimediaObject, 
                   'track' => $track);
    }


    /**
     * @Template()
     */
    public function seriesAction(MultimediaObject $multimediaObject)
    {
      $series = $multimediaObject->getSeries();
      $multimediaObjects = $series->getMultimediaObjects();

      return array('series' => $series,
                   'multimediaObjects' => $multimediaObjects);
    }

    /**
     * @Template()
     */
    public function relatedAction(MultimediaObject $multimediaObject)
    {
      $mmobjRepo = $this
        ->get('doctrine_mongodb.odm.document_manager')
        ->getRepository('PumukitSchemaBundle:MultimediaObject');
      $relatedMms = $mmobjRepo->findRelatedMultimediaObjects($multimediaObject);

      return array('multimediaObjects' => $relatedMms);
    }

    /**
     * @Route("/notallowed", name="pumukit_webtv_multimediaobject_notallowed")
     * @Template()
     */
    public function notallowedAction(Request $request)
    {
        return array();
    }

    /**
     * @Route("/authbroadcast/{id}", name="pumukit_webtv_multimediaobject_auth_broadcast")
     * @Template()
     */
    public function authbroadcastAction(MultimediaObject $multimediaObject, Request $request)
    {
        // TODO finish (CHECK VALUES)

        /* @var $request \Symfony\Component\HttpFoundation\Request */
        $session = $request->getSession();
        /* @var $session \Symfony\Component\HttpFoundation\Session\Session */

        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } elseif (null !== $session && $session->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = '';
        }

        if ($error) {
            // TODO: this is a potential security risk (see http://trac.symfony-project.org/ticket/9523)
            $error = $error->getMessage();
        }
        // last broadcast entered by the user
        $lastBroadcast = (null === $session) ? '' : $session->get(SecurityContext::LAST_USERNAME);

        $csrfToken = $this->container->get('form.csrf_provider')->generateCsrfToken('authenticate');

        $this->updateBreadcrumbs($multimediaObject);

        return array(
                     'multimediaObject' => $multimediaObject,
                     'last_broadcast' => $lastBroadcast,
                     'error'         => $error,
                     'csrf_token' => $csrfToken,
                     );
    }

    /**
     * @Route("/check/broadcast", name="pumukit_webtv_multimediaobject_check_broadcast")
     * @Method("POST")
     */
    public function checkAction()
    {
        // TODO
        throw new \RuntimeException('You must configure the check path to be handled by the firewall using form_login in your security firewall configuration.');
    }


    protected function getIntro($queryIntro=false)
    {
      $hasIntro = $this->container->hasParameter('pumukit2.intro');
      
      if ($queryIntro && filter_var($queryIntro, FILTER_VALIDATE_URL)) {
        $intro = $queryIntro;
      } elseif($hasIntro) {
        $intro = $this->container->getParameter('pumukit2.intro');
      } else {
        $intro = false;
      }

      return $intro;
    }

    protected function incNumView(MultimediaObject $multimediaObject, Track $track=null)
    {
      $dm = $this->get('doctrine_mongodb.odm.document_manager');
      $multimediaObject->incNumview();
      $track && $track->incNumview();
      $dm->persist($multimediaObject);
      $dm->flush();
    }


    protected function updateBreadcrumbs(MultimediaObject $multimediaObject)
    {
      $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
      $breadcrumbs->addMultimediaObject($multimediaObject);
    }


    public function preExecute(MultimediaObject $multimediaObject)
    {

      if($opencasturl = $multimediaObject->getProperty("opencasturl")) {
          $this->incNumView($multimediaObject);
          return $this->redirect($opencasturl);
      }
    }
}