<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class PlayerController extends Controller implements WebTVController
{
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

        if ($marks) {
            $marks = json_decode($marks->getValue(), true);
            if ($trimming) {
                $trimming = json_decode($trimming->getValue(), true);
                if (isset($trimming['trimming'])) {
                    $trimming = $trimming['trimming'];
                }

                foreach ($marks['marks'] as $chapt) {
                    $time = $chapt['s'];
                    if ($trimming['start'] <= $time && $trimming['end'] >= $time) {
                        $editorChapters[] = array('title' => $chapt['name'],
                                                  'real_time' => $time,
                                                  'time_to_show' => $time - $trimming['start'], );
                    }
                }
            }

            usort($editorChapters, function ($a, $b) {
                return $a['real_time'] > $b['real_time'];
            });
        }

        return $editorChapters;
    }

    /**
     * @deprecated Will be removed in version 2.4.x
     *             Use lines in this function instead
     */
    protected function testBroadcast(MultimediaObject $multimediaObject, Request $request)
    {
        $embeddedBroadcastService = $this->get('pumukitschema.embeddedbroadcast');
        $password = $request->get('broadcast_password');

        return $embeddedBroadcastService->canUserPlayMultimediaObject($multimediaObject, $this->getUser(), $password);
    }
}
