<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PlayerController extends Controller
{
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
            ($request->headers->get('PHP_AUTH_PW') == $broadcast->getPasswd()) &&
            (Broadcast::BROADCAST_TYPE_COR === $broadcast->getBroadcastTypeId())) {
            return true;
        }

        if (Broadcast::BROADCAST_TYPE_PRI === $broadcast->getBroadcastTypeId()) {
            return new Response($this->renderView('PumukitWebTVBundle:Index:403forbidden.html.twig', array()), 403);
        }

        $seriesUrl = $this->generateUrl('pumukit_webtv_series_index', array('id' => $multimediaObject->getSeries()->getId()), true);
        $redReq = new RedirectResponse($seriesUrl, 302);

        return new Response($redReq->getContent(), 401, array('WWW-Authenticate' => 'Basic realm="Resource not public."'));
    }

    protected function updateBreadcrumbs(MultimediaObject $multimediaObject)
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addMultimediaObject($multimediaObject);
    }

    protected function getIntro($queryIntro = false)
    {
        $intro = $this->container->getParameter('pumukit2.intro');

        if ($queryIntro && filter_var($queryIntro, FILTER_VALIDATE_URL)) {
            $intro = $queryIntro;
        }

        return $intro;
    }

    protected function getChapterMarks(MultimediaObject $multimediaObject)
    {
        //Get editor chapters for the editor template.
        //Once the chapter marks player plugin is created, this part won't be needed.
        $marks = $this->get('doctrine_mongodb.odm.document_manager')
                      ->getRepository('PumukitSchemaBundle:Annotation')
                      ->createQueryBuilder()
                      ->field('type')->equals('paella/marks')
                      ->field('multimediaObject')->equals(new \MongoId($multimediaObject->getId()))
                      ->getQuery()->getSingleResult();

        $trimming = $this->get('doctrine_mongodb.odm.document_manager')
                      ->getRepository('PumukitSchemaBundle:Annotation')
                      ->createQueryBuilder()
                      ->field('type')->equals('paella/trimming')
                      ->field('multimediaObject')->equals(new \MongoId($multimediaObject->getId()))
                      ->getQuery()->getSingleResult();


        $editorChapters = array();

        if($marks) {
            $marks = json_decode($marks->getValue(), true);
            if($trimming) {
                $trimming = json_decode($trimming->getValue(), true);
                if(isset($trimming['trimming']))
                    $trimming = $trimming['trimming'];

                foreach($marks['marks'] as $chapt) {
                    $time = $chapt['s'];
                    if($trimming['start'] <= $time && $trimming['end'] >= $time) {
                        $editorChapters[] = array('title' => $chapt['name'],
                                                  'real_time' => $time,
                                                  'time_to_show' => $time - $trimming['start']);
                    }
                }
            }
            
            usort($editorChapters, function($a, $b) {
                return $a['real_time'] > $b['real_time'];
            });

        }

        return $editorChapters;
    }
}
